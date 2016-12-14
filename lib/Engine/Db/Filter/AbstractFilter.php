<?php
/**
 * @namespace
 */
namespace Engine\Db\Filter;

use \Engine\Mvc\Model\Query\Builder,
    \Engine\Filter\SearchFilterInterface,
    \Engine\Crud\Grid\Filter\Field as FilterField,
    \Phalcon\Events\EventsAwareInterface,
    \Phalcon\DI\InjectionAwareInterface;

/**
 * Class database filters
 *
 * @category   Engine
 * @package    Db
 * @subpackage Filter
 */ 
abstract class AbstractFilter implements SearchFilterInterface, EventsAwareInterface, InjectionAwareInterface
{
    use \Engine\Tools\Traits\DIaware,
        \Engine\Tools\Traits\EventsAware;

    /**
     * Key for bound param
     * @var string
     */
    protected $_boundParamKey;

    /**
     * Crud grid filter field object
     * @var \Engine\Crud\Grid\Filter\Field
     */
    protected $_filterField;

    /**
     * Apply filter to query builder
     *
     * @param \Engine\Mvc\Model\Query\Builder $dataSource
     * @return string
     */
	abstract public function filterWhere(Builder $dataSource);

    /**
     * Apply filter to query builder
     *
     * @param \Engine\Mvc\Model\Query\Builder $dataSource
     * @return string
     */
    abstract public function getBoundParams(Builder $dataSource);

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
	}

    /**
     * Set key for bound param value
     *
     * @param string $key
     * @return \Engine\Db\Filter\AbstractFilter
     */
    public function setBoundParamKey($key)
    {
        $this->_boundParamKey = $key;
        return $this;
    }

    /**
     * Return key for bound param value
     *
     * @return string
     */
    public function getBoundParamKey()
    {
        return $this->_boundParamKey;
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
                case static::CRITERIA_EQ:
                    $compare = '=';
                    break;
                case static::CRITERIA_NOTEQ:
                    $compare = '!=';
                    break;
                case static::CRITERIA_MORE:
                    $compare = '>=';
                    break;
                case  static::CRITERIA_LESS:
                    $compare = '<=';
                    break;
                case static::CRITERIA_MORER:
                    $compare = '>';
                    break;
                case static::CRITERIA_LESSER:
                    $compare = '<';
                    break;
                case  static::CRITERIA_LIKE:
                    $compare = 'LIKE';
                    break;
                case static::CRITERIA_BEGINS:
                    $compare = 'LIKE';
                    break;
                case static::CRITERIA_IN;
                    $compare = 'IN';
                    break;
                case static::CRITERIA_NOTIN:
                    $compare = '<';
                    break;
                default:
                    $compare = 'NOT IN';
                    break;
            }
        }

        return $compare;
    }

    /**
     * Set crud grid filter field
     *
     * @param \Engine\Crud\Grid\Filter\Field $field
     *
     * @return \Engine\Search\Elasticsearch\Filter\AbstractFilter
     */
    public function setFilterField(FilterField $field)
    {
        $this->_filterField = $field;
        return $this;
    }

    /**
     * Return crud grid filter field
     *
     * @return \Engine\Crud\Grid\Filter\Field
     */
    public function getFilterField()
    {
        return $this->_filterField;
    }

}
