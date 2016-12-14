<?php
/**
 * @namespace
 */
namespace Engine\Mvc\Module\Service;

use Engine\Mvc\Module\Service\AbstractService,
    Engine\Acl\Authorizer;

/**
 * Class Acl
 *
 * @category   Engine
 * @package    Mvc
 * @subpackage Moduler
 */
class Auth extends AbstractService
{
    /**
     * Initializes acl
     */
    public function register()
    {
        $dependencyInjector = $this->getDi();
        $options = $this->_config->application->acl->toArray();

        $dependencyInjector->set('auth', function () use ($options, $dependencyInjector) {
            $adapter = new Authorizer($options, $dependencyInjector);
            return $adapter;
        });
    }
} 