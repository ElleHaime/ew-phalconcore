<?php
/**
 * @namespace
 */
namespace Engine\Search\Elasticsearch\Query;

use Elastica\Query\BoolQuery,
    Elastica\Query\AbstractQuery as Query;

/**
 * Class Builder
 *
 * @category    Engine
 * @package     Search
 * @subcategory Elasticsearch
 */
class Builder
{

    /**
     * @var \Engine\Mvc\Model
     */
    protected $_model;

    /**
     * @var \Elastica\Query\BoolQuery
     */
    protected $_query;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_query = new BoolQuery();
    }

    /**
     * Apply new filter condition to query
     *
     * @param \Elastica\Param $condition
     * @throws \Engine\Exception
     * @return \Engine\Search\Elasticsearch\Query\Builder
     */
    public function apply($condition)
    {
        if ($condition instanceof Query) {
            $this->_query->addMust($condition);
        } elseif (is_array($condition)) {
            foreach ($condition as $childCondition) {
                $this->apply($childCondition);
            }
        } else {
            throw new \Engine\Exception('Filter condition not correct');
        }

        return $this;
    }
    /**
     * Return query object
     *
     * @return \Elastica\Query\BoolQuery
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Set model
     *
     * @param \Engine\Mvc\Model $model
     * @param string $alias
     * @return \Engine\Mvc\Model\Query\Builder
     */
    public function setModel(\Engine\Mvc\Model $model)
    {
        $this->_model = $model;

        return $this;
    }

    /**
     * Return model object
     *
     * @return \Engine\Mvc\Model
     */
    public function getModel()
    {
        return $this->_model;
    }
}