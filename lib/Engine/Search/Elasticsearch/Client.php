<?php
/**
 * @namespace
 */
namespace Engine\Search\Elasticsearch;

use Elastica\Client as ElCient;

/**
 * Class Type
 *
 * @category    Engine
 * @package     Search
 * @subcategory Elasticsearch
 */
class Client extends ElCient implements
    \Phalcon\Events\EventsAwareInterface,
    \Phalcon\DI\InjectionAwareInterface
{
    use \Engine\Tools\Traits\DIaware,
        \Engine\Tools\Traits\EventsAware;

    /**
     * Returns the index for the given connection
     *
     * @param  \Engine\Search\Elasticsearch\ModelAdapter $model
     * @return \Engine\Search\Elasticsearch\Index Index for the given name
     */
    public function getIndex(\Engine\Search\Elasticsearch\ModelAdapter $model)
    {
        $prefix = $this->getConfig('prefix');
        $model->setSearchSourcePrefixKey($prefix);

        return new Index($model, $this);
    }
}