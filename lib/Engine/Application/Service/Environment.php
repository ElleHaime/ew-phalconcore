<?php
/**
 * @namespace
 */
namespace Engine\Application\Service;

use Engine\Application\Service\AbstractService;

/**
 * Class Environment
 *
 * @category   Engine
 * @package    Application
 * @subpackage Service
 */
class Environment extends AbstractService
{
    /**
     * Initializes the environment
     */
    public function init()
    {
        $dependencyInjector = $this->getDi();

        set_error_handler(array('\Engine\Error', 'normal'));
        register_shutdown_function(array('\Engine\Error', 'shutdown'));
        set_exception_handler(array('\Engine\Error', 'exception'));

        if ($this->_config->application->debug && $this->_config->application->profiler) {
            $profiler = new \Engine\Profiler();
            $dependencyInjector->set('profiler', $profiler);

            $debug = new \Phalcon\Debug();
            $debug->listen();
        }
    }
} 