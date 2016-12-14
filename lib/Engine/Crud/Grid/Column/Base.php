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
 * Class Base
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Grid
 */
class Base extends Column
{
    /**
     * Update grid container
     *
     * @param \Engine\Crud\Container\Grid\Adapter $container
     * @return \Engine\Crud\Grid\Column
     */
    public function updateContainer(\Engine\Crud\Container\Grid\Adapter $container)
    {
        $container->setColumn($this->_key, $this->_name, $this->_useTableAlias, $this->_useCorrelationTableName);
        return $this;
    }

    /**
     * Return render value
     *
     * @param array $row
     *
     * @return string
     */
    public function render($row)
    {
        $key = ($this->_useColumNameForKey) ? $this->_name : $this->_key;
        if (array_key_exists($key, $row)) {
            $value = $row[$key];
        } else {
            if ($this->_strict) {
                throw new \Engine\Exception("Key '{$key}' not exists in grid data row!");
            } else{
                return null;
            }
        }

        $value = $this->filter($value);

        return $value;
    }

}