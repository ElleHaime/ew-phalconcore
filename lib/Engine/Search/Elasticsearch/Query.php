<?php
/**
 * @namespace
 */
namespace Engine\Search\Elasticsearch;

use Elastica\Query as ElQuery,
    Elastica\Filter\AbstractFilter,
    Elastica\Query\AbstractQuery;

/**
 * Class Builder
 *
 * @category    Engine
 * @package     Search
 * @subcategory Elasticsearch
 */
class Query extends ElQuery
{

    /**
     * Sets the query
     *
     * @param  \Elastica\Query\AbstractQuery $query Query object
     * @return \Elastica\Query               Query object
     */
    public function setQuery(AbstractQuery $query)
    {
        if ($query instanceof AbstractQuery) {
            $query = $this->normalizeParams($query->toArray());
            if (!$query) {
                $query = ["bool" => ["must" => ["match_all" => []]]];
            }
        }

        return $this->setParam('query', $query);
    }

    /**
     * Set Filter.
     *
     * @param \Elastica\Query\AbstractQuery $filter Filter object
     *
     * @return $this
     *
     * @link    https://github.com/elasticsearch/elasticsearch/issues/7422
     * @deprecated Use Elastica\Query::setPostFilter() instead, this method will be removed in further Elastica releases
     */
    public function setFilter($filter)
    {
        if ($filter instanceof AbstractFilter) {
            trigger_error('Deprecated: Elastica\Query::setFilter() passing filter as AbstractFilter is deprecated. Pass instance of AbstractQuery instead.', E_USER_DEPRECATED);
        } elseif (!($filter instanceof AbstractQuery)) {
            throw new InvalidException('Filter must be instance of AbstractQuery');
        }

        trigger_error('Deprecated: Elastica\Query::setFilter() is deprecated and will be removed in further Elastica releases. Use Elastica\Query::setPostFilter() instead.', E_USER_DEPRECATED);

        return $this->setPostFilter($this->normalizeParams($filter->toArray()));
    }

    /**
     * Normalize filter array, remove all epmty values
     *
     * @param array $filters
     * @return array
     */
    public function normalizeParams(array $params)
    {
        foreach ($params as $key => $value) {
            if (is_object($value)) {
                if ($value instanceof \stdClass) {
                    $value = (array) $value;
                    //throw new \Engine\Exception('Incorect object value');
                } else {
                    $value = $value->toArray();
                }
            }
            if (is_array(($value))) {
                $value = $this->normalizeParams($value);
            }
            if ($value === false || $value === "" || (is_array($value) && count($value) == 0)) {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}