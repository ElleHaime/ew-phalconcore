<?php
/**
 * @namespace
 */
namespace Engine\Cache\Traits;

use \Phalcon\Cache\Backend as CacheService,
    \Phalcon\Cache\Backend\Memcache as MemcacheService,
    \Phalcon\Cache\Backend\Libmemcached as LibmemcachedService;

/**
 * Trait Chacher
 *
 * @category   Engine
 * @package    Cache
 */
trait Cacher
{
    /**
     * @var \Phalcon\Cache\BackendInterface
     */
    private $_cacheService;

    /**
     * Cache namespace prefix
     * @var string
     */
    private $_cacheNamespace;

    /**
     * Cache lifetime in seconds
     * @var integer
     */
    private $_cacheLifetime;

    /**
     * Cache lifetime in seconds for locking get data until updating
     * @var integer
     */
    private $_cacheLockLifetime = 10;

    /**
     * Is can be cache using locking logic
     * @var bool
     */
    private $_isLock = false;

    /**
     * Cache logic
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     * @throws \Engine\Exception
     */
    public function __call($name, array $arguments)
    {
        if (!strpos($name, 'Cache')) {
            throw new \Engine\Exception('The first argument to call() must be a valid callable.');
        }
        $lock = false;
        $delete = false;
        $update = false;
        if (strpos($name, 'CacheLock')) {
            $lock = true;
            $originName = str_replace('CacheLock', '', $name);
        } elseif (strpos($name, 'CacheDelete')) {
            $delete = true;
            $originName = str_replace('CacheDelete', '', $name);
        }  elseif (strpos($name, 'CacheUpdate')) {
            $update = true;
            $originName = str_replace('CacheUpdate', '', $name);
        } elseif (strpos($name, 'CacheLockUpdate')) {
            $lock = true;
            $update = true;
            $originName = str_replace('CacheLockUpdate', '', $name);
        } elseif (strpos($name, 'CacheUpdate')) {
            $update = true;
            $originName = str_replace('CacheUpdate', '', $name);
        } else {
            $originName = str_replace('Cache', '', $name);
        }

        $lastArgument = array_pop($arguments);
        if (is_array($lastArgument)) {
            $cacheArgument = false;
            if (isset($lastArgument['cacheLifetime'])) {
                $cacheArgument = true;
                $this->setCacheLifetime($lastArgument['cacheLifetime']);
            }
            if (isset($lastArgument['cacheLock'])) {
                $cacheArgument = true;
                $lock = true;
            }
            if (isset($lastArgument['cacheDelete'])) {
                $cacheArgument = true;
                $delete = true;
            }
            if (isset($lastArgument['cacheUpdate'])) {
                $cacheArgument = true;
                $update = true;
            }
            if (isset($lastArgument['cacheLockUpdate'])) {
                $cacheArgument = true;
                $update = true;
                $lock = true;
            }
            if (isset($lastArgument['cacheNamespace'])) {
                $cacheArgument = true;
                $this->setCacheNamespace($lastArgument['cacheNamespace']);
            }
            if (!$cacheArgument) {
                $arguments[] = $lastArgument;
            }
        }

        $callable = [get_class($this), $originName];
        $key = $this->_makeCacheKey($callable, $arguments);

        $cacheService = $this->getCacheService();

        if (!$this->_isLock) {
            $lock = false;
        }

        if ($delete) {
            if ($cacheService->exists($key)) {
                $cacheService->delete($key);
            }
            return true;
        }

        if ($update && $cacheService->exists($key) && !$lock) {
            $cacheService->delete($key);
        }

        if ($lock) {
            $data = $this->_getLock($key, $callable, $arguments, $update);
        } else {
            $data = $this->_get($key, $callable, $arguments);
        }

        //echo $data['output'];
        //return $data['result'];
        return $data;
    }

    /**
     * Get data from cache if not exists get origin data and save it
     *
     * @param string $key
     * @param array $callable
     * @param array $arguments
     *
     * @return mixed|string
     * @throws \Engine\Exception
     */
    private function _get($key, array $callable, array $arguments)
    {
        $cacheService = $this->getCacheService();
        if ($cacheService->exists($key)) {
            $data = $cacheService->get($key);
        } else {
            if (!is_callable($callable)) {
                throw new \Engine\Exception('The first argument to call() must be a valid callable.');
            }
            $data = $this->_getOriginData($callable, $arguments);

            $cacheService->save($key, $data, $this->_cacheLifetime);
        }

        return $data;
    }

    /**
     * Get data from cache using locking logic with lazy update data
     *
     * @param string $key
     * @param array $callable
     * @param array $arguments
     * @param bool $update
     *
     * @return mixed
     * @throws \Engine\Exception
     */
    private function _getLock($key, array $callable, array $arguments, $update = false)
    {
        $cacheService = $this->getCacheService();
        $lockKey = $key.'_lock';
        if ($cacheService->exists($key)) {
            $data = $cacheService->get($key);
        } elseif (!$update) {
            if ($cacheService->add($lockKey, 1, false, $this->_cacheLockLifetime)) {
                try {
                    $data = $this->_getOriginData($callable, $arguments);
                    $this->_setLock($key, $data);
                } finally {
                    $cacheService->delete($lockKey);
                }
            } else {
                while (!$cacheService->add($lockKey, 1, false, $this->_cacheLockLifetime)) {
                    usleep(100);
                }
                $data = $cacheService->get($key);
            }
        }

        if (!$this->_isValidLockCache($data)) {
            throw new \Engine\Exception('Cached lock data invalid');
        }
        if (!isset($data['_dc_cache'])) {
            throw new \Engine\Exception('Cached lock data is empty');
        }
        //check lifetime
        if ($update || (time() > $data['_dc_life_end'])) {
            //expired, save the same for a longer time for other connections
            if ($cacheService->add($lockKey, 1, false, $this->_cacheLockLifetime)) {
                try {
                    $data = $this->_getOriginData($callable, $arguments);
                    $this->_setLock($key, $data);
                } finally {
                    $cacheService->delete($lockKey);
                }
            }
        }

        return $data['_dc_cache'];
    }

    /**
     * Save data in cache with locking logic
     *
     * @param string $key
     * @param mixed $data
     *
     * @return bool
     */
    private function _setLock($key, &$data)
    {
        $cacheService = $this->getCacheService();
        // Place here "_lock" key check
        if (is_int($data) || $data === FALSE) {
            $cacheService->delete($key.'_lock');
        }
        $timeout = $this->_cacheLifetime;
        // Maximum timeout = 15 days - 1 second
        if ((int) $timeout == 0 || (int) $timeout > 1295999) {
            $timeout = 1295999;
        }
        $data = ['_dc_cache' => $data, '_dc_life_end' => time() + $timeout, '_dc_cache_time' => $timeout];
        $cacheService = $this->getCacheService();

        return $cacheService->save($key, $data , $timeout * 2);
    }

    /**
     * Validate cached data
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function _isValidLockCache($data)
    {
        return (is_array($data) &&
            isset($data['_dc_life_end']) && isset($data['_dc_cache_time']) &&
            !empty($data['_dc_life_end']) && !empty($data['_dc_cache_time'])
        ) ? true : false;
    }

    /**
     * Get original data
     *
     * @param array $callable
     * @param array $arguments
     *
     * @return mixed|string
     * @throws \Engine\Exception
     */
    private function _getOriginData(array $callable, array $arguments)
    {
        if (!is_callable($callable)) {
            throw new \Engine\Exception('The first argument to call() must be a valid callable.');
        }
        //$data = [];
        $data = '';

        //ob_start();
        //ob_implicit_flush(false);

        try {
            //$data['result'] = call_user_func_array($callable, $arguments);
            $data = call_user_func_array($callable, $arguments);
        }
        catch (\Exception $e) {
            //ob_end_clean();
            throw new \Engine\Exception($e->getMessage(), $e->getCode());
        }

        return $data;
    }

    /**
     * Make an key for the cache
     *
     * @param mixed $callable  A PHP callable
     * @param array $arguments An array of arguments to pass to the callable
     *
     * @return string The associated cache key
     * @access private
     */
    private function _makeCacheKey($callable, $arguments = [])
    {
        $key = md5(serialize($callable).serialize($arguments));
        return ($this->_cacheNamespace) ? $this->_cacheNamespace.':'.$key : $key;
    }

    /**
     * Set cache namespace
     *
     * @param string $namespace
     *
     * @return mixed
     */
    public function setCacheNamespace($namespace)
    {
        $namespace = str_replace(['http://www.', 'https://www.', 'http://', 'https://'], '', trim($namespace));
        $this->_cacheNamespace = $namespace;
        return $this;
    }

    /**
     * Set cache lifetime
     *
     * @param integer $lifetime
     *
     * @return mixed
     */
    public function setCacheLifetime($lifetime)
    {
        $this->_cacheLifetime = $lifetime;
        return $this;
    }

    /**
     * Set article service
     *
     * @param CacheService $cacheService
     *
     * @return mixed
     */
    public function setCacheService(CacheService $cacheService)
    {
        if (!$cacheService instanceof CacheService) {
            throw new \Engine\Exception('Object not instance of CacheService');
        }
        if (
            $cacheService instanceof LibmemcachedService ||
            $cacheService instanceof MemcacheService
        ) {
            $this->_isLock = true;
        }
        $this->_cacheService = $cacheService;

        return $this;
    }

    /**
     * Return article service object
     *
     * @return CacheService
     * @throws \Engine\Exception
     */
    public function getCacheService()
    {
        if (null === $this->_cacheService) {
            if ($this->_di) {
                $cacheService = $this->_di->get('cacheData');
                $this->setCacheService($cacheService);
            }
        }
        return $this->_cacheService;
    }
}