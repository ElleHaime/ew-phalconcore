<?php
/**
 * @namespace
 */
namespace Engine\Search\Elasticsearch\Filter;

use \Engine\Search\Elasticsearch\Query\Builder;

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
     * @param string $field
     * @param string $value
     * @param string $criteria
     */
    public function __construct($field, $value, $criteria = null)
    {
        $this->_field = $field;
        $this->_normalizeValue($value);
        $this->_value = $value;
        $this->_criteria = ($criteria !== null) ? $criteria : static::CRITERIA_IN;
    }

    /**
     * Apply filter to query builder
     *
     * @param \Engine\Search\Elasticsearch\Query\Builder $dataSource
     * @return string
     */
    public function filter(Builder $dataSource)
    {
        $values = $this->_value;
        if (!is_array($values)) {
            $values = [$values];
        }

        if (count($values) == 0) {
            return "";
        }

        $filter	= new \Elastica\Query\Terms($this->_field, $values);
        $boost = $this->getBoostParam();
        $filter->setParam('boost', $boost);
        if ($this->_criteria == self::CRITERIA_NOTEQ || $this->_criteria == self::CRITERIA_NOTIN) {
            $chlFilter = $filter;
            $filter = new \Elastica\Query\BoolQuery();
            $filter->addMustNot($chlFilter);
        }

        return $filter;
    }
}