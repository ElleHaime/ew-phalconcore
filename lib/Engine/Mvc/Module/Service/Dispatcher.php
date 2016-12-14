<?php
/**
 * @namespace
 */
namespace Engine\Mvc\Module\Service;

use Engine\Mvc\Module\Service\AbstractService,
    Phalcon\Mvc\Dispatcher as MvcDispatcher,
    Phalcon\Events\Manager as EventsManager,
    Phalcon\Mvc\Dispatcher\Exception as DispatchException;

/**
 * Class Dispatcher
 *
 * @category   Engine
 * @package    Mvc
 * @subpackage Moduler
 */
class Dispatcher extends AbstractService
{
    /**
     * Initializes dispatcher
     */
    public function register()
    {
        $dependencyInjector = $this->getDi();
        $eventsManager = $this->getEventsManager();

        $config = $this->_config;
        $defaultModuleDir = $this->_module->getDefaultModuleDirectory();
        $dependencyInjector->set('defualtModuleDir', function() use ($defaultModuleDir) {
            return $defaultModuleDir;
        });

        $moduleDirectory = $this->_module->getModuleDirectory();
        $dependencyInjector->set('moduleDirectory', function() use ($moduleDirectory) {
            return $moduleDirectory;
        });

        $dependencyInjector->set('dispatcher', function() use ($dependencyInjector, $eventsManager, $config)  {
            // Create dispatcher
            $dispatcher = new MvcDispatcher();

            //Attach a listener
            $eventsManager->attach("dispatch:beforeException", function($event, \Phalcon\Mvc\Dispatcher $dispatcher, $exception) use ($dependencyInjector, $config)   {

                if ($config->application->debug && $dependencyInjector->has('logger')) {
                    $logger = $dependencyInjector->get('logger');
                    $logger->error($exception->getMessage());
                }

                if ($dependencyInjector->has('debug')) {
                    $debug = $dependencyInjector->get('debug');
                    if ($debug) {
                        throw $exception;
                    }
                }

                //Handle 404 exceptions
                if ($exception instanceof DispatchException) {
                    $dispatcher->forward([
                        'controller' => 'error',
                        'action' => 'show404'
                    ]);
                    return false;
                }

                if ($dependencyInjector->get('request')->isAjax() == true) {
                }

                //Handle other exceptions
                $dispatcher->forward([
                    'controller' => 'error',
                    'action' => 'show503'
                ]);

                return false;
            });

            $eventsManager->attach("dispatch:beforeDispatchLoop", function($event, \Phalcon\Mvc\Dispatcher $dispatcher) {
                $dispatcher->setControllerName(\Phalcon\Text::lower($dispatcher->getControllerName()));
            });

            $dispatcher->setEventsManager($eventsManager);

            return $dispatcher;
        });
    }
} 