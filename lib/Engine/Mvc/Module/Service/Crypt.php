<?php
/**
 * @namespace
 */
namespace Engine\Mvc\Module\Service;

use Engine\Mvc\Module\Service\AbstractService;

/**
 * Class Crypt
 *
 * @category   Engine
 * @package    Mvc
 * @subpackage Moduler
 */
class Crypt extends AbstractService
{
    private $_key = 's7ArEfaZezUPRew7';

    /**
     * Initializes viewer
     */
    public function register()
    {
        $dependencyInjector = $this->getDi();
        $key = $this->_key;
        if (isset($this->_config->application->crypt->key)) {
            $key = $this->_config->application->crypt->key;
        }

        $dependencyInjector->set('crypt', function () use ($key) {
            //$crypt = new \Engine\Tools\Crypt2();
            $crypt = new \Phalcon\Crypt();
            $crypt->setCipher('blowfish');
            $crypt->setKey(substr($key, 0, 8));

            return $crypt;
        });
    }
}
