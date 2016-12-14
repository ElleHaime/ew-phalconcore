<?php
/**
 * @namespace
 */
namespace Engine\Search\Elasticsearch;

/**
 * Elasticsearch model adapter interface.
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Container
 */
interface ModelAdapter
{
    /**
     * Set search index prefix
     *
     * @param string $prefix
     * @return mixed
     */
    public function setSearchSourcePrefixKey($prefix);

    /**
     * Return search index prefix key
     *
     * @return string
     */
    public function getSearchSourcePrefixKey();

    /**
     * Return search index name
     *
     * @return string
     */
    public function getSearchSource();

    /**
     * Return search type name
     *
     * @return string
     */
    public function getSearchSourceType();

}