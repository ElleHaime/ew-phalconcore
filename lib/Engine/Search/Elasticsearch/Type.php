<?php
/**
 * @namespace
 */
namespace Engine\Search\Elasticsearch;

use Elastica\Type as ElType;

/**
 * Class Type
 *
 * @category    Engine
 * @package     Search
 * @subcategory Elasticsearch
 */
class Type extends ElType implements
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
     * Creates a new type object inside the given model.
     *
     * @param \Engine\Search\Elasticsearch\ModelAdapter $index Index Object
     */
    public function __construct(\Engine\Search\Elasticsearch\ModelAdapter $model)
    {
        $this->_model = $model;
        $this->_name = $model->getSearchSourceType();
        $this->setSource();
        if ($this->_name === null) {
            throw new \Engine\Exception("Elastic type source name not set!");
        }
    }

    /**
     * Returns index client
     *
     * @return \Elastica\Index Index object
     */
    public function getIndex()
    {
        if (null === $this->_index) {
            if ($this->_adapter instanceof Client) {
                $this->_index = $this->_adapter->getIndex($this->_model);
            } else {
                $this->_index = $this->getDi()->get($this->_adapter)->getIndex($this->_model);
            }
        }

        return $this->_index;
    }

    /**
     * Set elastic adapter name
     *
     * @param string $adapter
     * @return \Engine\Search\Elasticsearch\Type
     */
    public function setAdapter($adapter)
    {
        $this->_adapter = $adapter;
        return $this;
    }

    /**
     * Set elastic type name
     *
     * @param string $source
     * @return \Engine\Search\Elasticsearch\Type
     */
    public function setSource()
    {
    }
}