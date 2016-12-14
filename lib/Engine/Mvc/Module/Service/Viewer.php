<?php
/**
 * @namespace
 */
namespace Engine\Mvc\Module\Service;

use Engine\Mvc\Module\Service\AbstractService,
    Phalcon\Mvc\Dispatcher as MvcDispatcher,
    Phalcon\Events\Manager as EventsManager,
    Phalcon\Mvc\Dispatcher\Exception as DispatchException,
    Engine\Acl\Viewer as Service;

/**
 * Class Viewer
 *
 * @category   Engine
 * @package    Mvc
 * @subpackage Moduler
 */
class Viewer extends AbstractService
{
    /**
     * Initializes viewer
     */
    public function register()
    {
        $dependencyInjector = $this->getDi();
        $eventsManager = $this->getEventsManager();

        $dependencyInjector->set('viewer', function () use ($dependencyInjector, $eventsManager) {
            return new Service($dependencyInjector);
        });
    }
} 