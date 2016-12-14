<?php
/**
 * @namespace
 */
namespace Engine\Mvc\Module\Service;

/**
 * Class AbstractService
 *
 * @category   Engine
 * @package    Mvc
 * @subpackage Module
 */
abstract class AbstractService implements \Phalcon\DI\InjectionAwareInterface, \Phalcon\Events\EventsAwareInterface
{
    use \Engine\Tools\Traits\DIaware,
        \Engine\Tools\Traits\EventsAware;

    /**
     * Module
     * @var \Engine\Mvc\Module\Base
     */
    protected $_module;

    /**
     * Config
     * @var \Phalcon\Config
     */
    protected $_config;

	/**
	 * Constructor
	 *
     * @param \Engine\Mvc\Module\Base $module
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @param \Phalcon\Events\ManagerInterface $eventManager
     * @param \Phalcon\Config $config
	 */
	public function __construct(\Engine\Mvc\Module $module, \Phalcon\DiInterface $dependencyInjector, \Phalcon\Events\ManagerInterface $eventsManager, \Phalcon\Config $config)
	{
        $this->_module = $module;
		$this->setDi($dependencyInjector);
		$this->setEventsManager($eventsManager);
        $this->_config = $config;
	}
	
	/**
	 * Service register method
	 */
	abstract public function register();
}