<?php
/**
 * @namespace
 */
namespace Engine\Search\Elasticsearch;

use Elastica\Index as ElIndex;

/**
 * Class Index
 *
 * @category    Engine
 * @package     Search
 * @subcategory Elasticsearch
 */
class Index extends ElIndex implements
    \Phalcon\Events\EventsAwareInterface,
    \Phalcon\DI\InjectionAwareInterface
{
    use \Engine\Tools\Traits\DIaware,
        \Engine\Tools\Traits\EventsAware;

    /**
     * Model object
     * @var \Engine\Search\Elasticsearch\ModelAdapter
     */
    protected $_model;

    /**
     * Dependency injection adapter name
     * @var string
     */
    protected $_adapter = 'elastic';

    /**
     * Creates a new index object inside the given model.
     *
     * @param \Engine\Search\Elasticsearch\ModelAdapter $model Mvc model Object
     * @param \Engine\Search\Elasticsearch\Client
     */
    public function __construct(\Engine\Search\Elasticsearch\ModelAdapter $model, Client $client = null)
    {
        $this->_model = $model;
        $this->_client = $client;
        if ($client instanceof Client) {
            $prefix = $this->_client->getConfig('prefix');
            $this->_model->setSearchSourcePrefixKey($prefix);
            $this->_name = $this->_model->getSearchSource();
        }
    }

    /**
     * Returns a type object for the current index with the given name
     *
     * @param  string $type Type name
     * @return \Elastica\Type Type object
     */
    public function getType($type)
    {
        $type = new Type($this->_model);
        $type->setAdapter($this->_adapter);

        return $type;
    }

    /**
     * Returns index client.
     *
     * @return \Engine\Search\Elasticsearch\Client Index client object
     */
    public function getClient()
    {
        if (!$this->_client instanceof Client) {
            $this->_client = $this->getDi()->get($this->_adapter);

            $prefix = $this->_client->getConfig('prefix');
            $this->_model->setSearchSourcePrefixKey($prefix);
            $this->_name = $this->_model->getSearchSource();
        }

        return $this->_client;
    }

    /**
     * Set elastic adapter name
     *
     * @param string $adapter
     * @return \Engine\Search\Elasticsearch\Index
     */
    public function setAdapter($adapter)
    {
        $this->_adapter = $adapter;
        return $this;
    }
}