<?php
/**
 * @namespace
 */
namespace Engine\Mvc\View\Engine;

use Phalcon\Mvc\View\Engine,
    Phalcon\Mvc\View\EngineInterface;

/**
 * Jade
 *
 * Adapter to use Pug library as templating engine
 */
class Pug extends Engine implements EngineInterface
{

    protected $_pug;
    /**
     * Adapter constructor
     *
     * @param \Phalcon\Mvc\View $view
     * @param \Phalcon\DI $di
     * @param array $options
     */
    public function __construct($view, $di, $options = array())
    {
        //Initialize here the adapter
        parent::__construct($view, $di);
        $this->_pug = new \Pug\Pug($options);
    }
    /**
     * Renders a view using the template engine
     *
     * @param string $path
     * @param array $params
     */
    public function render($path, $params, $mustClean = false)
    {
        $content = $this->_pug->render($path, $params);
        if($mustClean) {
            $this->_view->setContent($content);
        } else {
            echo $content;
        }
    }
}