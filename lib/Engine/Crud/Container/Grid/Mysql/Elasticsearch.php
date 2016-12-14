<?php
/**
 * @namespace
 */
namespace Engine\Crud\Container\Grid\Mysql;

use Engine\Crud\Container\Grid\Mysql as Container,
    Engine\Crud\Container\Grid\Adapter as GridContainer,
    Engine\Search\Elasticsearch\Query\Builder,
    Engine\Search\Elasticsearch\Type,
    Engine\Search\Elasticsearch\Query;

/**
 * Class container for MySql with using ElasticSearch filters
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Container
 */
class Elasticsearch extends Container implements GridContainer
{
    /**
     * @var \Engine\Search\Elasticsearch\Query\Builder
     */
    protected $_elasticDataSource;

    /**
     * @var \Engine\Search\Elasticsearch\Type
     */
    protected $_elasticType;

    /**
     * Use data from search index
     * @var bool
     */
    protected $_useElasticData = false;

    /**
     * Return data array
     *
     * @return array
     */
    public function getData($dataSource)
    {
        $limit = $this->_grid->getLimit();
        $extraLimit = $this->_grid->getExtraLimit();

        $page = $this->_grid->getPage();
        $data = $this->_getPaginator($this->getElasticDataSource(), $extraLimit, $limit, $page);

        if (!$this->_useElasticData) {
            $data['data'] =  $this->_getData($dataSource, $data['data']);
        }

        return $data;
    }

    /**
     * Return data source object
     *
     * @return \Engine\Mvc\Model\Query\Builder
     */
    public function getElasticDataSource()
    {
        if (null === $this->_elasticDataSource) {
            $this->_setElasticDataSource();
        }
        return $this->_elasticDataSource;
    }

    /**
     * Set datasource
     *
     * @return void
     */
    protected function _setElasticDataSource()
    {
        $this->_elasticDataSource = new Builder();
        $this->_elasticDataSource->setModel($this->_model);

        $this->_elasticType = new Type($this->_model);
        $this->_elasticType->setDi($this->getDi());
        $this->_elasticType->setEventsManager($this->getEventsManager());
    }

    /**
     * Return filter object
     *
     * @return \Engine\Filter\SearchFilterInterface
     */
    public function getFilter()
    {
        $args = func_get_args();
        $type = array_shift($args);
        $className = $this->getFilterClass($type);
        $rc = new \ReflectionClass($className);
        $filter = $rc->newInstanceArgs($args);
        $filter->setDi($this->getDi());

        return $filter;
    }

    /**
     * Return filter class name
     *
     * @param string $type
     * @return string
     */
    public function getFilterClass($type)
    {
        return '\Engine\Search\Elasticsearch\Filter\\'.ucfirst($type);
    }

    /**
     * Setup paginator.
     *
     * @param \Engine\Search\Elasticsearch\Query\Builder $queryBuilder
     * @return \ArrayObject
     */
    protected function _getPaginator($queryBuilder, $extraLimit, $limit, $page, $total = false)
    {
        $query = new Query();
        $functionScoreParams = $this->_grid->getAttrib('function_score');
        if ($functionScoreParams) {
            $functionScore = $this->_getFunctionScoreQuery($queryBuilder, $functionScoreParams);
            $query->setQuery($functionScore);
        } else {
            $query->setQuery($queryBuilder->getQuery());
        }
        $sort = $this->_grid->getSortKey();
        if (null === $sort) {
            $sort = $this->_model->getOrderExpr();
        }
        if ($sort) {
            if (!$filterField = $this->_grid->getFilter()->getFieldByKey($sort)) {
                if (!$filterField = $this->_grid->getFilter()->getFieldByName($sort)) {
                    throw new Exception("Can't sort by '".$sort."' column, didn't find this field in search grid '".get_class($this->_grid)."'");
                }
            }
            $dependencyInjectorrection = $this->_grid->getSortDirection();
            if (null === $dependencyInjectorrection) {
                $dependencyInjectorrection = ($this->_model->getOrderAsc()) ? "asc" : "desc";
            }
            if ($filterField instanceof \Engine\Crud\Grid\Filter\Field\Join) {
                $sortName = $sort;
            } else {
                $sortName = $filterField->getName();
            }
            if ($dependencyInjectorrection) {
                $query->setSort([$sortName.'.sort' => ['order' => $dependencyInjectorrection]]);
            } else {
                $query->setSort($sortName.'.sort');
            }
        }

        $extraPage = (int) ceil(($limit*($page))/$extraLimit);
        $extraOffset = ($extraPage - 1)*$extraLimit;

        $query->setSize($extraLimit);
        $query->setFrom($extraOffset);

        /*$query->setHighlight([
            "number_of_fragments" => 3,
            "fragment_size" => 150,
            "tag_schema" => "styled",
            "fields" => [
            "_all"  => [ "pre_tags" => ["<em>"], "post_tags" => ["</em>"] ]

            'pre_tags' => ['<b>'],
            'post_tags' => ['</b>'],
            'fields' => [
                '_all' => []
            ]
        ]);*/

        $items = [];
        $position = $limit*($page-1)-($extraLimit*($extraPage-1));
        $results = $this->_elasticType->search($query);
        $total = $results->getTotalHits();
        $count = $results->count();
        $pages = (int) ceil($total/$limit);
        if ($total > 0) {
            $itemsTotal = ($position+$limit < $count) ? ($position+$limit) : $count;
            for ($i = $position; $i < $itemsTotal; ++$i) {
                $item = $results[$i]->getData();
                $items[] = ($this->_useElasticData) ? $item : $item['id'];
            }
        }
        $data = [
            'data' => $items,
            'page' => $page,
            'limit' => $limit,
            'mess_now' => count($items)
        ];

        if ($this->_grid->isCountQuery()) {
            $data['pages'] = $pages;
            $data['total_items'] = $total;
        }

        return $data;
    }

    /**
     * Get data from grid mysql datasource by primary field
     *
     * @param \Engine\Mvc\Model\Query\Builder $datasource
     * @param array $ids
     * @return array
     */
    protected function _getData($datasource, array $ids)
    {
        if (!$ids) {
            return false;
        }
        foreach ($ids as &$id) {
            $id = \Engine\Tools\Strings::quote($id);
        }
        $source = $datasource->getModel()->getSource();
        $primayField = $datasource->getModel()->getPrimary();

        return $datasource->andWhere($source.".".$primayField." IN (".implode(", ", $ids).")")->orderBy("FIELD (".$source.".".$primayField." ,".implode(", ", $ids).")")->getQuery()->execute();
    }

    /**
     * Set flag to use index data for build grid data
     *
     * @return \Engine\Crud\Container\Grid\Mysql\Elasticsearch
     */
    public function useIndexData()
    {
        $this->_useElasticData = true;
        return $this;
    }

    /**
     * Initialaize FunctionScore query object
     *
     * @param \Engine\Search\Elasticsearch\Query\Builder $queryBuilder
     * @param array $functionScoreParams
     *
     * @return \Elastica\Query\FunctionScore
     * @throws Exception
     */
    protected function _getFunctionScoreQuery($queryBuilder, array $functionScoreParams)
    {
        $functionScore = new \Elastica\Query\FunctionScore();
        $functionScore->setQuery($queryBuilder->getQuery());
        if (isset($functionScoreParams['boost'])) {
            $functionScore->setBoost($functionScoreParams['boost']);
        }
        if (isset($functionScoreParams['boost_mode'])) {
            $functionScore->setBoostMode($functionScoreParams['boost_mode']);
        }
        if (isset($functionScoreParams['functions'])) {
            if (!is_array($functionScoreParams['functions'])) {
                throw new Exception('ElasticSearch FunctionScore functions params is not array');
            }
            if (isset($functionScoreParams['functions']['weight'])) {
                $functionScore->addWeightFunction($functionScoreParams['functions']['weight']);
            }
            if (isset($functionScoreParams['functions']['field_value_factor'])) {
                if (!is_array($functionScoreParams['functions']['field_value_factor'])) {
                    throw new Exception('ElasticSearch FunctionScore functions field_value_factor params is not array');
                }
                if (isset($functionScoreParams['functions']['field_value_factor'][0])) {
                    foreach ($functionScoreParams['functions']['field_value_factor'] as $functionFieldFactor) {
                        $functionScore->addFunction('field_value_factor', $functionFieldFactor);
                    }
                } else {
                    $functionScore->addFunction('field_value_factor', $functionScoreParams['functions']['field_value_factor']);
                }
            }
        }

        return $functionScore;
    }


    /**
     * Nulled data source object
     *
     * @return \Engine\Crud\Container\Mysql
     */
    public function clearDataSource()
    {
        $this->_dataSource = null;
        $this->_elasticDataSource = null;
        return $this;
    }
}