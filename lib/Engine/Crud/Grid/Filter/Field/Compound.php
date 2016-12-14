<?php
/**
 * @namespace
 */
namespace Engine\Crud\Grid\Filter\Field;

use Engine\Crud\Grid\Filter\FieldInterface,
    Engine\Filter\SearchFilterInterface as Criteria,
    Engine\Crud\Container\AbstractContainer as Container,
    Engine\Crud\Grid\Filter\Field as BaseField;

/**
 * Grid filter field
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Grid
 */
class Compound extends Standart
{
    CONST OPERATOR_OR = 'OR';
    CONST OPERATOR_AND = 'AND';

    /**
     * Filter fields for compound
     * @var array
     */
    protected $_fields = [];

    /**
     * Compound form elements
     * @var array
     */
    private $_elements = [];

    /**
     * Filter compound operator
     * @var string
     */
    private $_operator;

    /**
     * Constructor
     *
     * @param string $label
     * @param string $name
     * @param array $fields
     * @param string $operator
     */
    public function __construct($label = null, $name = null, array $fields, $operator = 'OR')
    {
        parent::__construct($label, $name);
        $this->_fields = $fields;
        $this->_operator = $operator;
    }

    /**
     * Initialize field (used by extending classes)
     *
     * @return void
     */
    public function _init()
    {
        foreach ($this->_fields as $key => $field) {
            if (!$field instanceof BaseField) {
                throw new \Engine\Exception('Compound filter field '.$key.' not instance of Field');
            }
            $field->init($this->_gridFilter, $key);
            if (is_array($this->_default) && isset($this->_default[$key])) {
                $field->setDefault($this->_default[$key]);
            }
        }
    }

    /**
     * Return phalcon form elements
     *
     * @return array
     */
    public function getElement()
    {
        if (!empty($this->_elements)) {
            return $this->_elements;
        }

        foreach ($this->_fields as $key => $field) {
            if ($field instanceof Compound) {
                $this->_elements = array_merge($this->_elements, $field->getElement());
            } else {
                $element = $field->getElement();
                $this->_elements[] = $element;
            }
        }

        return $this->_elements;
    }

    /**
     * Return datasource filters
     *
     * @param \Engine\Crud\Container\AbstractContainer $container
     * @return \Engine\Filter\SearchFilterInterface
     */
    public function getFilter(Container $container)
    {
        $params = $this->getGridFilter()->getParams();
        $filters = [];
        foreach ($this->_fields as $key => $field) {
            if ($field instanceof Compound) {
                $field->setValue($params);
                $filters[] = $field->getFilter($container);
            } else {
                $value = (array_key_exists($key, $params)) ? $params[$key] : false;
                $field->setValue($value);
                $filters[] = $field->getFilter($container);
            }
        }

        return $container->getFilter('compound', $this->_operator, $filters);
    }

    /**
     * Return datasource filters
     *
     * @param \Engine\Crud\Container\AbstractContainer $container
     * @return \Engine\Filter\SearchFilterInterface
     */
    public function getValue()
    {
        $value = [];
        foreach ($this->_fields as $key => $field) {
            if ($field instanceof Compound) {
                $compoundValue = $field->getValue();
                $value = array_merge($value, $compoundValue);
            } else {
                $value[$key] = $field->getValue();
            }
        }

        return $value;
    }

    /**
     * Set params to compound fields
     *
     * @param mixed $value
     * @return void
     */
    public function setValue($params)
    {
        if (!$params) {
            return;
        }
        foreach ($this->_fields as $key => $field) {
            if (!$field instanceof BaseField) {
                throw new \Engine\Exception('Compound filter field '.$key.' not instance of Field');
            }
            if ($field instanceof Compound) {
                $field->setValue($params);
            } else {
                $value = (array_key_exists($key, $params)) ? $params[$key] : false;
                $field->setValue($value);
            }
        }
    }

    /**
     * Initialize form elements
     *
     * @param \Engine\Forms\Form $form
     * @throws \Engine\Exception
     * @return void
     */
    public function initForm(\Engine\Forms\Form $form)
    {
        foreach ($this->_fields as $key => $field) {
            if (!$field instanceof BaseField) {
                throw new \Engine\Exception('Compound filter field '.$key.' not instance of Field');
            }
            if ($field instanceof Compound) {
                $field->initForm($form);
            } else {
                $elements = $field->getElement();
                $field->updateField();
                if (!is_array($elements)) {
                    $elements = [$elements];
                }
                foreach ($elements as $element) {
                    $form->add($element);
                }
            }
        }
    }

    /**
     * Return compound fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->_fields;
    }

}
