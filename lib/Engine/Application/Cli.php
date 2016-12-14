<?php
/**
 * @namespace
 */
namespace Engine\Application;

use Phalcon\CLI\Console as PhApplication;

/**
 * Class Cli
 *
 * @category   Engine
 * @package    Application
 */
abstract class Cli extends PhApplication
{
    /**
     * Default module name.
     *
     * @var string
     */
    public static $defaultModule = 'cron';

    /**
     * Config path
     * @var string
     */
    protected $_configPath;

    /**
     * Loaders for different modes.
     *
     * @var array
     */
    protected $_services = [];

    /**
     * Application services namespace
     * @var string
     */
    protected $_serviceNamespace = '\Engine\Application\Service';
	
    /**
     * @var \Phalcon\Config
     */
    protected $_config;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (empty($this->_configPath)) {
            $class = new \ReflectionClass($this);
            throw new \Engine\Exception('Application has no config path: '.$class->getFileName());
        }

        $loader = new \Phalcon\Loader();
        $loader->registerNamespaces(['Engine' => ROOT_PATH.'/engine']);
        $loader->register();

        // create default di
        $dependencyInjector = new \Phalcon\DI\FactoryDefault\CLI();

        $this->_registerCondig($dependencyInjector);

        parent::__construct($dependencyInjector);
    }

    /**
     * Set configuration to Dependency Injection
     *
     * @param $dependencyInjector
     * @throws \Exception
     */
    private function _registerCondig($dependencyInjector)
    {
        $configPath = ROOT_PATH.$this->_configPath;
        if (!file_exists($configPath)) {
            throw new \Exception('Config dir not found!');
        }
        if (is_file($configPath)) {
            $globalConfig = include($configPath);
        } else {
            $globalConfig = new \Phalcon\Config();
            $files = scandir($configPath); // get all file names
            foreach ($files as $file) { // iterate files
                if ($file === '.' || $file === '..' || $file[0] === '_' || $file[0] === '.') {
                    continue;
                }
                $configFilePath = $configPath.'/'.$file;
                $fileInfo = pathinfo($configFilePath);
                if ($fileInfo['extension'] !== 'php') {
                    continue;
                }
                $config = include($configFilePath);
                $globalConfig->merge($config);
            }
        }

        $this->_config = $globalConfig;

        // Store config in the Di container
        $dependencyInjector->setShared('config', $globalConfig);
    }

    /**
     * Runs the application, performing all initializations
     *
     * @return void
     */
    public function run()
    {
        $modules = $this->_config->get('modules');
        if (!$modules) {
            $modules = (object) [];
        }

        $dependencyInjector = $this->_dependencyInjector;
        $config = $this->_config;

        if (isset($this->_config->application)) {
            if ($this->_config->application->debug) {
                if (!isset($this->_config->application->useCachingInDebugMode)) {
                    $this->_config->application->useCachingInDebugMode = false;
                }
            }
        }

        $dependencyInjector->set('modules', function () use ($modules) {
            return $modules;
        });

        // Set application event manager
        $eventsManager = new \Phalcon\Events\Manager();

        // register enabled modules
        $enabledModules = [];
        if (!empty($modules)) {
            foreach ($modules as $module => $enabled) {
                if (!$enabled) {
                    continue;
                }
                $moduleName = ucfirst($module);
                $enabledModules[$module] = [
                    'className' => $moduleName.'\Module',
                    'path' => ROOT_PATH.'/apps/modules/'.$moduleName.'/Module.php',
                ];
            }

            if (!empty($enabledModules)) {
                $this->registerModules($enabledModules);
            }
        }

        // Init services and engine system
        foreach ($this->_services as $serviceName) {
            $service = $this->_getService($serviceName);
            $service = new $service($dependencyInjector, $eventsManager, $config);
            if (!($service instanceof \Engine\Application\Service\AbstractService)) {
                throw new \Engine\Exception("Service '{$serviceName}' not instanceof AbstractService");
            }
            $service->init();
        }

        // Set default services to the DI
        $this->setEventsManager($eventsManager);
        $dependencyInjector->setShared('eventsManager', $eventsManager);
        $dependencyInjector->setShared('app', $this);
    }

    /**
     * Return application service full class name
     *
     * @param string $serviceName
     * @return string
     */
    private function _getService($serviceName)
    {
        return (class_exists($serviceName) ? $serviceName : $this->_serviceNamespace."\\".ucfirst($serviceName));
    }

    /**
     * Return string content
     * @return string
     */
    public function getOutput()
    {
        return $this->handle()->getContent();
    }

    /**
     * Clear application cache
     */
    public function clearCache()
    {

    }
}