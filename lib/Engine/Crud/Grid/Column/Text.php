<?php
/**
 * @namespace
 */
namespace Engine\Crud\Grid\Column;

use Engine\Crud\Grid\Column,
    Engine\Crud\Grid,
    Engine\Crud\Container\Grid as GridContainer;
	
/**
 * Class Text
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Grid
 */
class Text extends Base
{
    /**
     * Max length for rendered row value
     * @var integer
     */
    protected $_maxLength;

    /**
     * Constructor
     *
     * @param string $title
     * @param string $name
     * @param bool $isSortable
     * @param bool $isHidden
     * @param int $width
     * @param bool $isEditable
     * @param string $fieldKey
     * @param integer $maxLength
     */
    public function __construct(
        $title,
        $name = null,
        $isSortable = true,
        $isHidden = false,
        $width = 160,
        $isEditable = true,
        $fieldKey = null,
        $maxLength = null
    ) {
        parent::__construct($title, $name, $isSortable, $isHidden, $width, $isEditable, $fieldKey);
        $this->_maxLength = $maxLength;
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

        if ($this->_maxLength) {
            $value = substr($value, 0, $this->_maxLength);
        }

        return $value;
    }

    /**
     * Set max rendered row length
     *
     * @param integer $length
     *
     * @return \Engine\Crud\Grid\Column\Text
     */
    public function setMAxLength($length)
    {
        $this->_maxLength = (int) $length;

        return $this;
    }
	
}