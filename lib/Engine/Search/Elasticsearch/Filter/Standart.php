<?php
/**
 * @namespace
 */
namespace Engine\Search\Elasticsearch\Filter;

use \Engine\Search\Elasticsearch\Query\Builder;

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
     * Filter field
     * @var array
     */
    protected $_field;

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
     * @param string $field
     * @param string $value
     * @param string $criteria
     */
    public function __construct($field, $value, $criteria = null)
    {
        $this->_field = $field;
        $this->_normalizeValue($value);
        $this->_value = $value;
        $this->_criteria = ($criteria !== null) ? $criteria : static::CRITERIA_EQ;
    }

    /**
     * Apply filter to query builder
     *
     * @param \Engine\Search\Elasticsearch\Query\Builder $dataSource
     * @return string
     */
    public function filter(Builder $dataSource)
    {
        $model = $dataSource->getModel();
        if ($this->_field === static::COLUMN_ID) {
            $expr = $model->getPrimary();
        } elseif ($this->_field === static::COLUMN_NAME) {
            $expr = $model->getNameExpr();
        } else {
            $expr = $this->_field;
        }
        $boost = $this->getBoostParam();
        $slop = $this->getSlopParam();

        if (null === $this->_value) {
            $filter = new \Elastica\Query\Missing($expr);
            //$filterMissing->addParam("existence", true);
            //$filterMissing->addParam("null_value", true);
            $boost = $this->getBoostParam();
        } else {
            $filter = new \Elastica\Query\BoolQuery();
            if ($this->_criteria === static::CRITERIA_EQ || $this->_criteria === static::CRITERIA_IN) {
                $filterTerm = new \Elastica\Query\Term();
                $filterTerm->setTerm($expr, $this->_value, $boost);
                $filter->addMust($filterTerm);
            } elseif ($this->_criteria === static::CRITERIA_NOTEQ || $this->_criteria === static::CRITERIA_NOTIN) {
                $filterTerm = new \Elastica\Query\Term();
                $filterTerm->setTerm($expr, $this->_value, $boost);
                $filter->addMustNot($filterTerm);
            } elseif ($this->_criteria === static::CRITERIA_LIKE) {
                $filterQueryString = new \Elastica\Query\QueryString($this->_value);
                $filterQueryString->setDefaultField($expr);
                $filter->addMust($filterQueryString);
            } elseif ($this->_criteria === static::CRITERIA_BEGINS) {
                //$filter = new \Elastica\Query\Prefix();
                //$filter->setPrefix($expr, $this->_value);
                //$filterBool->addMust($filter);
                $filterQueryString = new \Elastica\Query\QueryString($this->_value);
                $filterQueryString->setDefaultField($expr);
                $filter->addMust($filterQueryString);
            } elseif ($this->_criteria === static::CRITERIA_MORE) {
                $filterRange = new \Elastica\Query\Range($expr, ['gt' => $this->_value]);
                $filter->addMust($filterRange);
            } elseif ($this->_criteria === static::CRITERIA_LESS) {
                $filterRange = new \Elastica\Query\Range($expr, ['lt' => $this->_value]);
                $filter->addMust($filterRange);
            } elseif ($this->_criteria === static::CRITERIA_MORER) {
                $filterRange = new \Elastica\Query\Range($expr, ['gte' => $this->_value]);
                $filters[] = $filter;
            } elseif ($this->_criteria === static::CRITERIA_LESSER) {
                $filterRange = new \Elastica\Query\Range($expr, ['lte' => $this->_value]);
                $filters[] = $filter;
            }
        }

        return $filter;

    }
}