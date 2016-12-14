<?php
/**
 * @namespace
 */
namespace Engine\Application\Service;

use Engine\Application\Service\AbstractService;

/**
 * Class Url
 *
 * @category   Engine
 * @package    Application
 * @subpackage Service
 */
class Url extends AbstractService
{
    /**
     * Initializes the baseUrl
     */
    public function init()
    {
        $dependencyInjector = $this->getDi();

        /**
         * The URL component is used to generate all kind of urls in the
         * application
         */
        $url = new \Phalcon\Mvc\Url();
        $url->setBaseUri($this->_config->application->baseUri);
        $dependencyInjector->set('url', $url);

        if (isset($this->_config->application->siteUrl)) {
            $siteUrl = $this->_config->application->siteUrl;
            if (strpos($siteUrl, 'http://') === false) {
                $siteUrl = 'http://'.$siteUrl;
            }
            $dependencyInjector->set('siteUrl', function () use ($siteUrl) {
                return $siteUrl;
            }, true);
        }
    }
} 