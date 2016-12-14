<?php
/**
 * @namespace
 */
namespace Engine\Cache\Backend;

use Phalcon\Cache\Backend\Libmemcached as MemcacheBase,
	Phalcon\Cache\BackendInterface,
	Phalcon\Cache\Exception;

/**
 * Libmemcached
 *
 * This backend uses memcached as cache backend
 */
class Libmemcached extends MemcacheBase implements BackendInterface
{
	/**
	 * Add an item to the memcache server
	 *
	 * @param string $key
	 * @param string $var
	 * @param bool $flag
	 * @param integer $expire
	 *
	 * @return bool
	 */
	public function add($key, $var, $flag, $expire)
	{
		if (!$this->_memcache) {
			$this->_connect();
		}
		return $this->_memcache->add($key, $var, $flag, $expire);
	}

}
