<?php
/**
 * @namespace
 */
namespace Engine\Mvc\Module\Service;

use Engine\Mvc\Module\Service\AbstractService;

/**
 * Class View
 *
 * @category   Engine
 * @package    Mvc
 * @subpackage Moduler
 */
class View extends AbstractService
{
    /**
     * Initializes Volt engine
     */
    public function register()
    {
        $dependencyInjector = $this->getDi();
        $eventsManager = $this->getEventsManager();

        $config = $this->_config;
        $moduleDirectory = $this->_module->getModuleDirectory();
        $defaultModuleDir = $this->_module->getDefaultModuleDirectory();

        $dependencyInjector->set('view', function () use ($dependencyInjector, $moduleDirectory, $defaultModuleDir, $eventsManager, $config) {

            $view = new \Phalcon\Mvc\View();
            $view->setViewsDir($moduleDirectory.'/View/');
            $view->setLayoutsDir('layouts/');

            $view->registerEngines([
                ".volt" => 'viewEngine'
            ]);

            // Attach a listener for type "view"
            if (!$config->application->debug) {
                $eventsManager->attach('view', function ($event, $view) use ($dependencyInjector) {
                    if ($event->getType() == 'notFoundView') {
                        $dependencyInjector->get('logger')->error('View not found - "'.$view->getActiveRenderPath().'"');
                    }
                });

                $view->setEventsManager($eventsManager);
            } elseif ($config->application->profiler) {
                $eventsManager->attach('view', function ($event, $view) use ($dependencyInjector) {
                    if ($dependencyInjector->has('profiler')) {
                        if ($event->getType() == 'beforeRender') {
                            $dependencyInjector->get('profiler')->start();
                        }
                        if ($event->getType() == 'afterRender') {
                            $dependencyInjector->get('profiler')->stop($view->getActiveRenderPath(), 'view');
                        }
                    }
                    if ($event->getType() == 'notFoundView') {
                        $dependencyInjector->get('logger')->error('View not found - "'.$view->getActiveRenderPath().'"');
                    }
                });
                $view->setEventsManager($eventsManager);
            }

            return $view;
        });
    }
} 