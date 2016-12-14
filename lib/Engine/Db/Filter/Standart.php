<?php
/**
 * @namespace
 */
namespace Engine\Db\Filter;

use \Engine\Mvc\Model\Query\Builder;

/**
 * Compound filters
 *
 * @category   Engine
 * @package    Db
 * @subpackage Filter
 */ 
class Standart extends AbstractFilter 
{
	/**
	 * Filter column
	 * @var array
	 */
	protected $_column;
	
	/**
	 * Filter value
	 * @var string|integer
	 */
	protected $_value;
	
	/**
	 * Filter criteria
	 * @var string
	 */
	protected $_criteria;

	/**
	 * Constructor
	 * 
	 * @param string $column
	 * @param mixed $value
	 * @param string $criteria
	 */
	public function __construct($column, $value, $criteria = null)
	{
        if (!is_string($column)) {
            throw new \Engine\Exception('Column name has incorect data type');
        }
		$this->_column = $column;
		$this->_value = $value;
		$this->_criteria = ($criteria !== null) ? $criteria : static::CRITERIA_EQ;
	}

    /**
     * Apply filter to query builder
     *
     * @param \Engine\Mvc\Model\Query\Builder $dataSource
     * @return string
     */
	public function filterWhere(Builder $dataSource)
	{
		$model = $dataSource->getModel();
		if ($this->_column === static::COLUMN_ID) {
            $expr = $model->getPrimary();
            $alias = $dataSource->getAlias();
		} elseif ($this->_column === static::COLUMN_NAME) {
			$expr = $model->getNameExpr();
            $alias = $dataSource->getAlias();
		} else {
			$expr = $this->_column;
            $alias = $dataSource->getCorrelationName($this->_column);
		}
        if (!$alias) {
            throw new \Engine\Exception('Field \''.$this->_column.'\' not found in query builder');
        }
        $compare = $this->getCompareCriteria($this->_criteria, $this->_value);

        if (null === $this->_value) {
            return $alias.'.'.$expr.' '.$compare.' NULL';
        }
		$key = $this->getFilterField()->getKey();

        $this->setBoundParamKey($alias.'_'.$expr.'_'.$key);

        return $alias.'.'.$expr.' '.$compare.' :'.$this->getBoundParamKey().':';
	}

    /**
     * Return bound params array
     *
     * @param \Engine\Mvc\Model\Query\Builder $dataSource
     * @return array
     */
    public function getBoundParams(Builder $dataSource)
    {
        if (null === $this->_value) {
            return null;
        }

        $exprEq = $this->_value;
        $exprLike = '%'.$exprEq.'%';
        $exprBegins = $exprEq.'%';

        $value = $exprEq;
        $key = $this->getBoundParamKey();
        if ($this->_criteria === self::CRITERIA_LIKE) {
            $value = $exprLike;
        } elseif ($this->_criteria === self::CRITERIA_BEGINS) {
            $value = $exprBegins;
        }

        /*if ((strlen(floatval($this->_value)) !== strlen($this->_value)) || (strpos($this->_value, ' ') !== false)) {
            $adapter =  $dataSource->getModel()->getReadConnection();
            $this->_value = $adapter->escapeString($this->_value);
        }*/

        return [$key => $value];
    }
}