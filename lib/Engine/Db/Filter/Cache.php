<?php
/**
 * @namespace
 */
namespace Engine\Db\Filter;

use \Engine\Mvc\Model\Query\Builder;

/**
 * Cache search filter
 *
 * @category   Engine
 * @package    Db
 * @subpackage Filter
 */ 
class Cache extends Search 
{
	/**
	 * Cache
	 * @var string|array
	 */
	protected $_cache;
	
	/**
	 * Fitler criteria
	 * @var string
	 */
	protected $_criteria;
	
	/**
	 * Constructor
	 * 
	 * @param string $value
	 * @param array $columns
	 * @param array|string $cache
	 * @param string $criteria
	 */
	public function __construct($value , $columns, $cache, $criteria = null)
	{
		parent::__construct($columns, $value);
		
		if (empty($cache)) {
			$this->_cache = false;
		} elseif (is_array($cache)) {
			$this->_cache = $cache;
		} else {
			$this->_cache = $this->getDi()->get($cache);
		}
        $this->_criteria = ($criteria !== null) ? $criteria : static::CRITERIA_EQ;
		$this->_setValue();
	}

    /**
     * Apply filter to query builder
     *
     * @param \Engine\Mvc\Model\Query\Builder $dataSource
     * @return string
     */
    public function filterWhere(Builder $dataSource)
    {
		if (!$this->_cache) {
			return parent::filterWhere($dataSource);
		}
		if (count($this->_value) == 0) {
			return false;
		}
		if (count($this->_value) == 1) {
			$this->_value = $this->_value[0];
			return parent::filterWhere($dataSource);
		}

        $column = array_keys($this->_columns)[0];

        $model = $dataSource->getModel();
        if ($column === static::COLUMN_ID) {
            $expr = $model->getPrimary();
            $alias = $dataSource->getAlias();
        } elseif ($column=== static::COLUMN_NAME) {
            $expr = $model->getNameExpr();
            $alias = $dataSource->getAlias();
        } else {
            $expr = $column;
            $alias = $dataSource->getCorrelationName($column);
        }
        if (!$alias) {
            throw new \Engine\Exception('Field \''.$column.'\' not found in query builder');
        }
        $compare = $this->getCompareCriteria($this->_criteria, $this->_value);

        if (null == $this->_value) {
            return $alias.'.'.$expr.' '.$compare.' NULL';
        }

        $this->setBoundParamKey($alias.'_'.$expr);

        return $alias.'.'.$expr.' '.$compare.' (:'.$this->getBoundParamKey().':)';
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
        $key = $this->getBoundParamKey();

        $values = $this->_value;
        if (!is_array($values)) {
            $values = [$values];
        }
        $adapter =  $dataSource->getModel()->getReadConnection();
        foreach ($values as &$value) {
            $value = $adapter->escapeString($value);
        }
        if (count($values) == 0) {
            return false;
        }

        return [$key => $values];
    }
	
	/**
	 * Is find value in cache
	 * 
	 * @return bool
	 */
	public function isCached()
	{
		if (count($this->_value) == 0) {
			return false;
		} else { 
			return true;
		}
	}
	
	/**
	 * Find and set search value from cache
	 * 
	 * @return void
	 */
	protected function _setValue()
	{
		if ($this->_criteria === static::CRITERIA_EQ) {
			$this->_value = array_search($this->_value, $this->_cache);
		} elseif ($this->_criteria === static::CRITERIA_LIKE) {
			$this->_value = $this->_arraySearch($this->_value, $this->_cache);
		} elseif ($this->_criteria === static::CRITERIA_BEGINS) {
			$this->_value = $this->_arraySearch($this->_value, $this->_cache, true);
		}
	}
	
	/**
	 * Search value in array
	 * 
	 * @param string $needle
	 * @param array $haystack
	 * @param bool $type
     * @return array
	 */
	protected function _arraySearch($needle, array $haystack, $type = NULL)
	{
		$keys = [];
		$needle = strtolower($needle);
		foreach ($haystack as $key => $value) {
			$value = strtolower($value);
			if (strlen($needle) > strlen($value)) {
				continue;
			} elseif (strlen($needle) == strlen($value)) {
				if ($needle == $value) {
					$keys[] = $key;
				}
			} elseif ($type === true) {
				$value = substr($value,0,strlen($needle));
				if ($needle == $value) {
					$keys[] = $key;
				}
			} elseif ($type === false) {
				$value = substr($value,-strlen($needle));
				if ($needle == $value) {
					$keys[] = $key;
				}
			} elseif (strpos($value, $needle) !== false) {
				$keys[] = $key; 
			}
		}
		
		return $keys;
	}
}
