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
class Match extends AbstractFilter
{
    /**
     * Match fileds
     * @var array
     */
    protected $_field = [];

    /**
     * Filter expression
     * @var string
     */
    protected $_expr;

    /**
     * @param array $fields
     * @param $expr
     */
    public function __construct(array $fields, $expr)
	{
		$this->_expr = $expr;
		$this->_fields = $fields;
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
        $filters = new \Elastica\Query\MultiMatch();
        $fields = [];
        foreach ($this->_fields as $field => $criteria) {
            if ($field === static::COLUMN_ID) {
                $value = (int) $this->_expr;
                if (!is_numeric($this->_expr)) {
                    continue;
                }
                $field = $model->getPrimary();
            } elseif ($field === static::COLUMN_NAME) {
                $field = $model->getNameExpr();
            }
            $boost = $this->getBoostParam($field);
            $fields[] = $field.'^'.$boost;
        }

        $slop = $this->getSlopParam();
        $filters->setParam('slop', $slop);
        $filters->setFields($fields);
        $filters->setTieBreaker(0.3);
        $filters->setType(\Elastica\Query\MultiMatch::TYPE_BEST_FIELDS);
        //$filters->setType(\Elastica\Query\MultiMatch::TYPE_MOST_FIELDS);
        $filters->setQuery($this->_expr);

        return $filters;
	}

    /**
     * Return filter boost
     *
     * @param string $field
     *
     * @return string|void
     */
    public function getBoostParam($field)
    {
        $boost = '1.0';
        if (!$this->_filterField) {
            return $boost;
        }
        $boostParam = $this->_filterField->getAttrib('boost');
        if (!$boostParam) {
            return $boost;
        }
        if (!is_array($boostParam)) {
            $boost = $boostParam;
        } else {
            if (isset($boostParam[$field])) {
                $boost = $boostParam[$field];
            }
        }

        return $boost;
    }

}
