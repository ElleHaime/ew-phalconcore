<?php
/**
 * @namespace
 */
namespace Engine\Crud\Container\Grid;

use Engine\Crud\Container\Mysql as Container,
    Engine\Crud\Container\Grid\Adapter as GridContainer,
    Engine\Crud\Grid,
	Engine\Mvc\Model,
    Engine\Mvc\Model\Query\Builder,
    Engine\Paginator\Adapter\Grid as Paginator,
    Engine\Crud\Container\Grid\Exception;

/**
 * Class container for Mysql.
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Container
 */
class Mysql extends Container implements GridContainer
{	
	/**
	 * Grid object
	 * @var \Engine\Crud\Grid
	 */
	protected $_grid;

    /**
     * Data source columns
     * @var array
     */
    protected $_columns = [];

    /**
     * Flag
     * @var bool
     */
    protected $_separateQuery = false;

    /**
     * Constructor
     *
     * @param \Engine\Crud\Grid $grid
     * @param array $options
     */
    public function __construct(\Engine\Crud\Grid $grid, $options = [])
	{
		$this->_grid = $grid;
		if (!is_array($options)) {
            $options = [self::MODEL => $options];
        }
		$this->setOptions($options);
	}
	
	/**
	 * Set datasource
	 * 
	 * @return void
	 */
	protected function _setDataSource()
	{
		$this->_dataSource = $this->_model->queryBuilder();

	    foreach ($this->_joins as $table) {
	    	$this->_dataSource->columnsJoinOne($table);
	    }
        $this->_dataSource->columns($this->_columns);

	    foreach ($this->_conditions as $cond) {
            if (is_array($cond)) {
                $this->_dataSource->addWhere($cond['cond'], $cond['params']);
            } else {
	    	    $this->_dataSource->addWhere($cond);
            }
	    }
        $sort = $this->_grid->getSortKey();
        if (null === $sort) {
            $sort = $this->_model->getOrderExpr();
        }
        $dependencyInjectorrection = $this->_grid->getSortDirection();
        if (null === $dependencyInjectorrection) {
            $dependencyInjectorrection = ($this->_model->getOrderAsc()) ? 'ASC' : 'DESC';
        }
        if ($sort) {
            $alias = $this->_dataSource->getCorrelationName($sort);
            if ($alias) {
                $sort = $alias.'.'.$sort;
            }
        	if ($dependencyInjectorrection) {
        		$this->_dataSource->orderBy($sort.' '.$dependencyInjectorrection);
        	} else {
        		$this->_dataSource->orderBy($sort);
        	}
        }
	}
	
	/**
	 * Return data array
	 * 
	 * @return array
	 */
	public function getData($dataSource)
	{
		$limit = $this->_grid->getLimit();
        $extraLimit = $this->_grid->getExtraLimit();

        $page = $this->_grid->getPage();
        $total = false;
        if (!$this->_grid->isCountQuery()) {
            $total = $this->_grid->getStaticCount();
        }
        if ($this->_separateQuery) {
            $primaryDatasource = clone($dataSource);
            if ($primaryDatasource instanceof Builder) {
                $primaryDatasource->reset(Builder::COLUMNS);
                $primaryDatasource->columnsId();
                $primaryPaginator = $this->_getPaginator($primaryDatasource, $extraLimit, $limit, $page, $total);
                $ids = [];
                foreach ($primaryPaginator['data'] as $row) {
                    $ids[] = $row['id'];
                }
                $idDataSource = clone($dataSource);
                $idDataSource->reset(Builder::WHERE);
                $inFilter = new \Engine\Db\Filter\In(\Engine\Db\Filter\In::COLUMN_ID, $ids);
                $inFilter->applyFilter($idDataSource);
                unset($primaryPaginator['data']);
                $paginator = $this->_getPaginator($idDataSource, $extraLimit, $limit, 1, $total);
                $paginator = array_merge($paginator, $primaryPaginator);
            }
        } else {
            $paginator = $this->_getPaginator($dataSource, $extraLimit, $limit, $page, $total);
        }

		return $paginator;
	}
	
	/**
	 * Return filter object
	 *
	 * @return \Engine\Filter\SearchFilterInterface
	 */
	public function getFilter()
	{
		$args = func_get_args();
		$type = array_shift($args);
		$className = $this->getFilterClass($type);
		$rc = new \ReflectionClass($className);
		$filter = $rc->newInstanceArgs($args);
        $filter->setDi($this->getDi());

		return $filter;
	}
	
	/**
	 * Return filter class name
	 * 
	 * @param string $type
	 * @return string
	 */
	public function getFilterClass($type)
	{
		return '\Engine\Db\Filter\\'.ucfirst($type);
	}
	
	/**
	 * Setup paginator.
	 *
     * @param \Engine\Mvc\Model\Query\Builder $queryBuilder
     * @param integer $extraLimit
     * @param integer $limit
     * @param integer $page
     * @param integer $total
     * @return array
     */
	protected function _getPaginator($queryBuilder, $extraLimit, $limit, $page, $total = false)
    {
        $config = [
            'builder' => $queryBuilder,
            'extra_limit' => $extraLimit,
            'limit' => $limit,
            'page' => $page
        ];
        if ($total) {
            $config['total'] = $total;
        }
        $paginator = new Paginator($config);

    	return $paginator->getPaginate();
	}
	
	/** 
	 * Update rows by primary id values
	 * 
	 * @param array $id
	 * @param array $data
	 * @return bool|array
	 */
	public function update(array $ids, array $data)
	{
        $db = $this->_model->getWriteConnection();
		$db->begin();
	    try {
	        $primary = $this->_model->getPrimary();
			unset($data[$primary]);
            $records = $this->_model->findByIds($ids);
            foreach ($records as $record) {
                if (!$record->update($data)) {
                    $db->rollBack();
                    $messages = [];
                    foreach ($record->getMessages() as $message)  {
                        $messages[] = $message->getMessage();
                    }
                    throw new Exception(implode(', ', $messages));
                }
            }
			$results = $this->_updateJoins($ids, $data);
		} catch (\Engine\Exception $e) {
            $db->rollBack();
			throw $e;
		}
		$db->commit();
		
		return true;
	}
	
	/**
	 * Update data to joins tables by reference ids
	 * 
	 * @param array $ids
	 * @param array $data
     * @return bool|array
	 */
	protected function _updateJoins(array $ids, array $data)
	{
        foreach ($this->_joins as $model) {
            $referenceColumn = $model->getReferenceFields($this->_model);
            if (!$referenceColumn) {
                continue;
            }
            $records = $model->findByColumn($referenceColumn, $ids);
            foreach ($records as $record) {
                if (!$record->update($data)) {
                    $messages = [];
                    foreach ($record->getMessages() as $message)  {
                        $messages[] = $message->getMessage();
                    }
                    throw new Exception(implode(', ', $messages));
                }
            }
        }
	    
	    return true; 
	}
	
	/**
	 * Delete rows by primary value
	 * 
	 * @param array $ids
	 * @return bool|array
	 */
	public function delete(array $ids)
	{
        $db = $this->_model->getWriteConnection();
        $db->begin();
        try {
            $records = $this->_model->findByIds($ids);
            foreach ($records as $record) {
                if (!$record->delete()) {
                    $db->rollBack();
                    $messages = [];
                    foreach ($record->getMessages() as $message)  {
                        $messages[] = $message->getMessage();
                    }
                    throw new Exception(implode(', ', $messages));
                }
            }
        } catch (\Engine\Exception $e) {
            $db->rollBack();
            throw $e;
        }
        $db->commit();

        return true;
	}

    /**
     * Set flag for separate executing logic for twp steps
     * first execute query with all filters and limit, offset but only for getting primary keys,
     * second execute query to get all needed data using primary index with filter IN
     *
     * @param bool $flag
     * @return \Engine\Crud\Container\Grid\Mysql
     */
    public function separateQuery($flag = true)
    {
        $this->_separateQuery = $flag;
        return $this;
    }

}