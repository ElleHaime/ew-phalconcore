<?php
/**
 * @namespace
 */
namespace Engine\Crud\Form\Field;

use Engine\Crud\Form\Field;

/**
 * Numeric field
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Form
 */
class Numeric extends Field
{
    /**
     * Form element type
     * @var string
     */
    protected $_type = 'numeric';

    /**
     * Field value type.
     * @var string
     */
    protected $_valueType = 'int';

    /**
     * Min field value
     * @var integer
     */
    protected $_min;

    /**
     * Max field value
     * @var integer
     */
    protected $_max;

    /**
     * Constructor
     *
     * @param string $label
     * @param string $name
     * @param string $desc
     * @param bool $required
     * @param int $width
     * @param string|integer $default
     * @param int $min
     * @param int $max
     */
    public function __construct(
        $label = null,
        $name = null,
        $desc = null,
        $required = false,
        $width = 280,
        $default = null,
        $min = 0,
        $max = 0xfffffffff
    ) {
        parent::__construct($label, $name, $desc, $required, $width, $default);
        $this->_min = $min;
        $this->_max = $max;
    }

    /**
     * Initialize field (used by extending classes)
     *
     * @return void
     */
    protected function _init()
    {
        $this->_filters = [];
        $this->_filters[] = [
            'filter' => 'float',
            'options' => []
        ];

        /*$this->_validators[] = [
            'validator' => 'Regex',
            'options' => ['pattern' => '/^\d+|$/']
        ];

        $this->_validators[] = [
            'validator' => 'Between',
            'options' => [
                'minimum' => $this->_min,
                'maximum' => $this->_max,
                'messages' => "The value must be between '".$this->_min."' and '".$this->_max."'"
            ]
        ];*/

        if ($this->isRequire()) {
            $this->_validators[] = [
                'validator' => 'PresenceOf',
                'options' => []
            ];
        }
    }

    /**
     * Return min value
     *
     * @return string
     */
    public function getMinValue()
    {
        return $this->_min;
    }

    /**
     * Return max value
     *
     * @return string
     */
    public function getMaxValue()
    {
        return $this->_max;
    }
}