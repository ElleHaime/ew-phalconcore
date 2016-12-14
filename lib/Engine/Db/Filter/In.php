<?php
/**
 * @namespace
 */
namespace Engine\Db\Filter;

use \Engine\Mvc\Model\Query\Builder;

/**
 *  Match filter
 *
 * @category   Engine
 * @package    Db
 * @subpackage Filter
 */
class In extends Standart
{
    /**
     * Constructor
     *
     * @param string $column
     * @param mixed $value
     * @param string $criteria
     */
    public function __construct($column, $value, $criteria = null)
    {
        $this->_column = $column;
        $this->_value = $value;
        $this->_criteria = ($criteria !== null) ? $criteria : static::CRITERIA_IN;
    }

    /**
     * Apply filter to table select object
     *
     * @param \Engine\Mvc\Model\Query\Builder $dataSource
     * @param mixed $value
     */
    public function applyFilter($dataSource)
    {
        $where = $this->filterWhere($dataSource);
        if (!$where) {
            return false;
        }
        $params = $this->getBoundParams($dataSource);
        if ($params === false) {
            return false;
        }
        $dataSource->andWhere($where, $params);
        return;
        if (null == $params) {
            $dataSource->andWhere($where, $params);
        } else {
            switch ($this->_criteria) {
                case static::CRITERIA_IN;
                    $dataSource->inWhere($where, $params);
                    break;
                case static::CRITERIA_NOTIN:
                    $dataSource->notInWhere($where, $params);
                    break;
            }
        }
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
        $key = $this->getFilterField()->getKey();

        $this->setBoundParamKey($alias.'_'.$expr.'_'.$key);

        if (null == $this->_value) {
            return $alias.'.'.$expr.' '.$compare.' (NULL)';
        }

        return $alias.'.'.$expr.' '.$compare.' ('.$this->getBoundParamKey().')';
    }

    /**
     * Set key for bound param value
     *
     * @param string $key
     * @return \Engine\Db\Filter\AbstractFilter
     */
    public function setBoundParamKey($key)
    {
        if (null == $this->_value) {
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
     * Return bound params array
     *
     * @param \Engine\Mvc\Model\Query\Builder $dataSource
     * @return array
     */
    public function getBoundParams(Builder $dataSource)
    {
        if (null == $this->_value) {
            return null;
        }

        if (count($this->_value) == 0) {
            return false;
        }

        return $this->_value;
    }

    /**
     * Return compare criteria
     *
     * @param string $criteria
     * @param mixed $value
     * @return string
     */
    public function getCompareCriteria($criteria, $value)
    {
        if (null === $value) {
            if ($criteria == static::CRITERIA_NOTEQ || $criteria == static::CRITERIA_NOTIN) {
                $compare = 'IS NOT';
            } else {
                $compare = 'IS';
            }
        } else {
            switch ($criteria) {
                case static::CRITERIA_NOTEQ:
                case static::CRITERIA_NOTIN:
                    $compare = 'NOT IN';
                    break;

                case static::CRITERIA_EQ:
                case static::CRITERIA_IN:
                default:
                    $compare = 'IN';
                    break;
            }
        }

        return $compare;
    }
}