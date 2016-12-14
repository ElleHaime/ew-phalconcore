<?php
/**
 * @namespace
 */
namespace Engine\Application\Service;

use Engine\Application\Service\AbstractService;

/**
 * Class Loader
 *
 * @category   Engine
 * @package    Application
 * @subpackage Service
 */
class Loader extends AbstractService
{
    /**
     * Initializes the loader
     */
    public function init()
    {
        $dependencyInjector = $this->getDi();
        $eventsManager = $this->getEventsManager();

        $modules = $dependencyInjector->get('modules');

        $modulesNamespaces = [];
        foreach ($modules as $module => $enabled) {
            if (!$enabled) {
                continue;
            }
            $modulesNamespaces[ucfirst($module)] = $this->_config->application->modulesDir . ucfirst($module);
        }

        if (isset($this->_config->application->engineDir)) {
            $modulesNamespaces['Engine'] = $this->_config->application->engineDir;
        }
        if (isset($this->_config->application->librariesDir)) {
            $modulesNamespaces['Library'] = $this->_config->application->librariesDir;
        }

        $loader = new \Phalcon\Loader();
        $loader->registerNamespaces($modulesNamespaces);

        if (
            (isset($this->_config->application->debug) && $this->_config->application->debug) &&
            (isset($this->_config->installed) && $this->_config->installed)
        ) {
            $eventsManager->attach('loader', function ($event, $loader, $className) use ($dependencyInjector) {
                if ($event->getType() == 'afterCheckClass') {
                    $dependencyInjector->get('logger')->error("Can't load class '".$className."'");
                }
            });
            $loader->setEventsManager($eventsManager);
        }

        $loader->register();

        $dependencyInjector->set('loader', $loader);
    }
} 