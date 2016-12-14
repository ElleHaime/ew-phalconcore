<?php
/**
 * @namespace
 */
namespace Engine\Application\Service;

/**
 * Class AbstractService
 *
 * @category   Engine
 * @package    Application
 * @subpackage Service
 */
abstract class AbstractService implements \Phalcon\DI\InjectionAwareInterface, \Phalcon\Events\EventsAwareInterface
{
	use \Engine\Tools\Traits\DIaware,
		\Engine\Tools\Traits\EventsAware;

    /**
     * Config object
     * @var \Phalcon\Config
     */
    protected $_config;

	/**
	 * Constructor
	 * 
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @param \Phalcon\Events\ManagerInterface $eventManager
	 * @param \Phalcon\Config $config
	 */
	public function __construct(\Phalcon\DiInterface $dependencyInjector, \Phalcon\Events\ManagerInterface $eventsManager, \Phalcon\Config $config)
	{
		$this->setDi($dependencyInjector);
		$this->setEventsManager($eventsManager);
        $this->_config = $config;
	}
	
	/**
	 * Service init method
	 *
	 * @return void
	 */
	abstract public function init();
}