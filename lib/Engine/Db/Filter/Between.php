<?php
/**
 * @namespace
 */
namespace Engine\Db\Filter;

use \Engine\Mvc\Model\Query\Builder;

/**
 *  Between filter
 *
 * @category   Engine
 * @package    Db
 * @subpackage Filter
 */
class Between extends Standart
{
    /**
     * Filter column
     * @var string
     */
    protected $_column;

    /**
     * Max value
     * @var string|integer
     */
    protected $_min;

    /**
     * Max value
     * @var string|integer
     */
    protected $_max;

    /**
     * @var string
     */
    protected $_criteria;

    /**
     * @param string $column
     * @param string|integer $min
     * @param string|integer $max
     * @param string $criteria
     */
    public function __construct($column, $min, $max, $criteria = null)
	{
        if (!is_string($column)) {
            throw new \Engine\Exception('Column name has incorect data type');
        }
		$this->_column = $column;
		$this->_min = $min;
		$this->_max = $max;
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
        $adapter = $adapter =  $dataSource->getModel()->getReadConnection();
        $alias = $dataSource->getCorrelationName($this->_column);
        $key = $this->getFilterField()->getKey();

        $this->setBoundParamKey($alias.'_'.$this->_column.'_'.$key);

		return $alias.'.'.$this->_column.($this->_criteria == self::CRITERIA_NOTEQ) ? ' NOT ' : ' '. 'BETWEEN :'.$this->getBoundParamKey().'_min: AND :'.$this->getBoundParamKey().'_max:';
	}

    /**
     * Return bound params array
     *
     * @param \Engine\Mvc\Model\Query\Builder $dataSource
     * @return array
     */
    public function getBoundParams(Builder $dataSource)
    {
        $key = $this->getBoundParamKey();
        $adapter = $adapter =  $dataSource->getModel()->getReadConnection();

        return [$key.'_min' => $this->_min, $key.'_max' => $this->_max];
    }

}
