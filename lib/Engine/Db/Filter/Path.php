<?php
/**
 * @namespace
 */
namespace Engine\Db\Filter;

use Engine\Mvc\Model\Query\Builder,
    Engine\Crud\Grid\Filter\Field,
    Phalcon\Mvc\Model\Relation;

/**
 * Join path filter
 *
 * @category   Engine
 * @package    Db
 * @subpackage Filter
 */
class Path extends AbstractFilter
{
    /**
     * Filter path
     * @var string|array
     */
    protected $_path;

    /**
     * Filter
     * @var \Engine\Crud\Grid\Filter\Field
     */
    protected $_filterField;

    /**
     * Filter
     * @var \Engine\Db\Filter\AbstractFilter
     */
    protected $_filter;

    /**
     * Filter value
     * @var string|integer
     */
    protected $_value = null;

    /**
     * Filter category model
     * @var bool|string
     */
    protected $_category;

    /**
     * Build query with all joins
     * @var bool
     */
    protected $_fullJoin = true;

    /**
     * Constructor
     *
     * @param string|array $path
     * @param \Engine\Crud\Grid\Filter\Field $filterField
     * @param \Engine\Search\Elasticsearch\Filter\AbstractFilter $filter
     * @param string $pathCategory
     */
    public function __construct($path, Field $filterField, AbstractFilter $filter, $pathCategory = false)
    {
        $this->_path = $path;
        $this->_filterField = $filterField;
        $this->_filter = $filter;
        $this->_category = $pathCategory;
    }

    /**
     * Apply filter to table select object
     *
     * @param \Engine\Mvc\Model\Query\Builder $dataSource
     * @param mixed $value
     */
    public function applyFilter($dataSource)
    {
        if (!$this->_path) {
            return $this->_filter->applyFilter($dataSource);
        }

        $where = $this->filterWhere($dataSource);
        if (!$where) {
            return false;
        }

        $params = $this->getBoundParams($dataSource);
        if ($params === false) {
            return false;
        }

        $dataSource->andWhere($where, $params);
    }

    /**
     * Apply filter to query builder
     *
     * @param \Engine\Mvc\Model\Query\Builder $dataSource
     * @return string
     */
    public function filterWhere(\Engine\Mvc\Model\Query\Builder $dataSource)
    {
        //$dataSource->columnsJoinOne($this->_path);
        $model = $dataSource->getModel();
        $joinPath = $model->getRelationPath($this->_path);

        if (!$joinPath) {
            throw new \Engine\Exception('Relations to model \''.get_class($model).'\' by path \''.implode(', ', $this->_path).'\' not valid');
        }

        if ($this->_fullJoin) {
            $dataSource->joinPath($joinPath);
            return $this->_filter->filterWhere($dataSource);
        }

        $relation = array_shift($joinPath);
        $this->_processJoins($relation, $joinPath);
        if (!$this->_value) {
            return false;
        }
        $expr = $relation->getFields();
        $alias = $dataSource->getCorrelationName($expr, array_reverse($this->_path)[0]);
        $key = $this->getFilterField()->getKey();

        $this->setBoundParamKey($alias.'_'.$expr.'_'.$key);
        if (count($this->_value) == 1) {
            //$compare = $this->getCompareCriteria($this->_criteria, $this->_value);
            return $alias.'.'.$expr.' = :'.$this->getBoundParamKey().':';
        }

        return $alias.'.'.$expr.' IN ('.$this->getBoundParamKey().')';
    }

    /**
     * Return bound params array
     *
     * @param \Engine\Mvc\Model\Query\Builder $dataSource
     * @return array
     */
    public function getBoundParams(\Engine\Mvc\Model\Query\Builder $dataSource)
    {
        if ($this->_fullJoin) {
            return $this->_filter->getBoundParams($dataSource);
        }
        if (!$this->_value) {
            return false;
        }
        if (is_array($this->_value)) {
            return $this->_value;
        }

        $key = $this->getBoundParamKey();

        return [$key => $this->_value];
    }

    /**
     * Set key for bound param value
     *
     * @param string $key
     * @return \Engine\Db\Filter\AbstractFilter
     */
    public function setBoundParamKey($key)
    {
        if (!is_array($this->_value)) {
            $this->_boundParamKey = $key;
        } else {
            $count = count($this->_value);
            $countMin = $count - 1;
            $values = [];
            foreach ($this->_value as $i => $value) {
                $this->_boundParamKey .= ':'.$key.'_'.$i.':';
                if ($i < $countMin) {
                    $this->_boundParamKey .= ',';
                }
                $values[$key.'_'.$i] = $value;
            }
            $this->_value = $values;
        }

        return $this;
    }

    /**
     * Process all search query joins
     *
     * @param \Phalcon\Mvc\Model\Relation $relation
     * @param array $joinPath
     * @return array|bool
     */
    protected function _processJoins(\Phalcon\Mvc\Model\Relation $relation, array $joinPath)
    {
        $refModel = $relation->getReferencedModel();
        $refModel = new $refModel;
        $refFields = $relation->getReferencedFields();
        $options = $relation->getOptions();

        $dataSourceIn = $refModel->queryBuilder();
        $dataSourceIn->setColumn($refFields);
        $relation = array_shift($joinPath);

        if ($joinPath) {
            if (!$ids = $this->_processJoins($relation, $joinPath)) {
                return false;
            }
            $expr = $relation->getFields();
            if (count($ids) > 1) {
                $dataSourceIn->inWhere($expr, $ids);
            } else {
                $dataSourceIn->andWhere($expr.' = :'.$expr.':', [$expr => $ids]);
            }
        } else {
            $this->_filter->applyFilter($dataSourceIn);
        }
        //$dataSourceIn->columnsId();
        $result = $dataSourceIn->getQuery()->execute()->toArray();

        if (count($result) == 0) {
            return false;
        }

        foreach ($result as $row) {
            $this->_value[$row[$refFields]] = $row[$refFields];
        }

        if (count($this->_value) == 1) {
            $this->_value = $this->_value[0];
        }

        return true;
    }

}