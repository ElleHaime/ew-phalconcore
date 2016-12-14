<?php
/**
 * @namespace
 */
namespace Engine\Forms\Element;

/**
 * Class Html
 *
 * @category    Engine
 * @package     Forms
 * @subcategory Element
 */
class Html extends \Engine\Forms\Element\Text implements \Engine\Forms\ElementInterface
{
    /**
     * Form element description
     * @var string
     */
    protected $_html = '';

    /**
     * @param string $name
     * @param array $attributes
     */
    public function __construct($name, array $attributes=null)
    {
        if (isset($attributes['html']) ) {
            $this->_html = $attributes['html'];
            unset($attributes['html']);
        }

        parent::__construct($name, $attributes);
    }

    /**
     * If element is need to be rendered in default layout
     *
     * @return bool
     */
    public function useDefaultLayout() {
        return false;
    }

    /**
     * Render form element
     *
     * @param array $attributes
     *
     * @return string
     */
    public function render(array $attributes = null)
    {
        return $this->_html;
    }
}