<?php
/**
 * @namespace
 */
namespace Engine\Mvc;

use Phalcon\Mvc\ModuleDefinitionInterface;

/**
 * Class Base Module
 *
 * @category    Engine
 * @package     Mvc
 */
abstract class Module implements ModuleDefinitionInterface
{
    /**
     * Module name
     * @var string
     */
    protected $_moduleName;

    /**
     * Services array
     * @var array
     */
    protected $_services = [];

    /**
     * Services array
     * @var array
     */
    protected $_loaders = [];

    /**
     * @var \Phalcon\Config
     */
    protected $_config;

    /**
     * @var \Phalcon\DiInterface
     */
    protected $_di;

    /**
     * Application services namespace
     * @var string
     */
    protected $_serviceNamespace = '\Engine\Mvc\Module\Service';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_checkModuleName();
        $this->_di = \Phalcon\DI::getDefault();
        $this->_config = $this->_di->get('config');
    }

    /**
     * Registers an autoloader related to the module
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     *
     * @return void
     */
    public function registerAutoloaders(\Phalcon\DiInterface $dependencyInjector = null)
    {
        $namespaces = [];
        foreach ($this->_loaders as $load) {
            $load = ucfirst($load);
            $namespace = $this->_moduleName.'\\'.$load;
            $dependencyInjectorrectory = $this->getModuleDirectory().'/'.$load;
            $namespaces[$namespace] = $dependencyInjectorrectory;
        }

        $loader = new \Phalcon\Loader();
        $loader->registerNamespaces($namespaces);
        $loader->register();
    }

    /**
     * Registers an autoloader related to the module
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     *
     * @return \Phalcon\Events\Manager
     */
    public function registerServices(\Phalcon\DiInterface $dependencyInjector = null)
    {
        //Create an event manager
        $eventsManager = $dependencyInjector->get('eventsManager');

        // Init services and engine system
        foreach ($this->_services as $serviceName) {
            $service = $this->_getService($serviceName);
            $service = new $service($this, $dependencyInjector, $eventsManager, $this->_config);
            if (!($service instanceof \Engine\Mvc\Module\Service\AbstractService)) {
                throw new \Engine\Exception("Service '{$serviceName}' not instance of AbstractService");
            }
            $service->register();
        }

        /*************************************************/
        //  Initialize dispatcher
        /*************************************************/
        if (!$this->_config->application->debug) {
            //$eventsManager->attach("dispatch:beforeException", new \Engine\Plugin\NotFound());
            //$eventsManager->attach('dispatch:beforeExecuteRoute', new \Engine\Plugin\CacheAnnotation());
        }

        return $eventsManager;
    }


    /**
     * Return module name
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->_moduleName;
    }

    /**
     * Return module directory
     *
     * @return string
     */
    public function getModuleDirectory()
    {
        return $this->_config->application->modulesDir.$this->_moduleName;
    }

    /**
     * Return default module directory
     *
     * @return string
     */
    public function getDefaultModuleDirectory()
    {
        $defaultModule = 'core';
        if (isset($this->_config->application->defaultModule)) {
            $defaultModule = $this->_config->application->defaultModule;
        }
        return $this->_config->application->modulesDir.ucfirst($defaultModule);
    }

    /**
     * Check and normalize module name
     *
     * @throws \Engine\Exception
     * @return void
     */
    protected function _checkModuleName()
    {
        if (empty($this->_moduleName)) {
            $class = new \ReflectionClass($this);
            throw new \Engine\Exception('Module class has no module name: '.$class->getFileName());
        } else {
            $this->_moduleName = ucfirst($this->_moduleName);
        }
    }

    /**
     * Return module service full class name
     *
     * @param string $serviceName
     * 
     * @return string
     */
    protected function _getService($serviceName)
    {
        return (class_exists($serviceName) ? $serviceName : $this->_serviceNamespace."\\".ucfirst($serviceName));
    }
}