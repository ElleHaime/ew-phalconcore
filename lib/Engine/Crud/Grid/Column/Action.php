<?php
/**
 * @namespace
 */
namespace Engine\Crud\Grid\Column;

use Engine\Crud\Grid\Column,
    Engine\Crud\Grid,
    Engine\Crud\Container\Grid as GridContainer,
    Phalcon\Filter;

/**
 * Class Action
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Grid
 */
class Action extends Column
{
    /**
     * Column type.
     * @var string
     */
    protected $_type = 'action';

    /**
     * Action items
     * @var string
     */
    protected $_items;

    /**
     * Action template title
     * @var null|string
     */
    protected $_title;

    /**
     * Action icon image
     * @var string
     */
    protected $_icon;

    /**
     * Construct
     *
     * @param array $items
     * @param string $title
     * @param string $icon
     * @param int $width
     */
    public function __construct(array $items = [], $width = 160)
    {
        parent::__construct(null, null, false, false, $width, false, null);

        $this->_items = $items;
    }

    /**
     * Update grid container
     *
     * @param \Engine\Crud\Container\Grid\Adapter $container
     * @return \Engine\Crud\Grid\Column
     */
    public function updateContainer(\Engine\Crud\Container\Grid\Adapter $container)
    {
        return $this;
    }

    /**
     * Return render value
     *
     * @param array $row
     * @return string
     */
    public function render($row)
    {
        $items = [];
        foreach ($this->_items as $item) {
            $items[] = $this->renderItem($row, $item);
        }
        $code = implode(" ", $items);

        return $code;
    }

    /**
     * Render item action
     *
     * @param array $row
     * @param array $item
     * @return string
     */
    public function renderItem(array $row, array $item)
    {
        $attribs = $this->getAttribs();
        $template = $item['template'];
        $icon = $item['icon'];
        if (isset($item['name'])) {
            $name = $item['name'];
        } elseif ($item['title']) {
            $name = $item['title'];
        }

        $href = \Engine\Tools\Strings::generateStringTemplate($template, $row, '{', '}');
        $code = '<a href="'.$href.'"';

        foreach ($attribs as $name => $value) {
            $code .= ' '.$name.'="'.$value.'"';
        }
        $name = \Engine\Tools\Strings::generateStringTemplate($name, $row, '{', '}');
        $code .= '><span>'.$name.'</span></a>';

        return $code;
    }

    /**
     * Return action items
     *
     * @return array
     */
    public function getItems()
    {
        return $this->_items;
    }
}