<?php
/**
 * @namespace
 */
namespace Engine\Forms;

/**
 * Class Form
 *
 * @category    Engine
 * @package     Forms
 */
class Form extends \Phalcon\Forms\Form
{
    /**
     * Action methods
     */
    CONST METHOD_GET     = 'get';
    CONST METHOD_POST    = 'post';

    /**
     * Action method
     * @var string
     */
    protected $_method = self::METHOD_GET;

    /**
     * Set from action method
     *
     * @param string $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->_method = $method;
        return $this;
    }

    /**
     * Return action form method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Set data to form
     *
     * @param array $data
     * @param bool $validate
     *
     * @return \Engine\Forms\Form
     * @throws \Engine\Exception
     */
    public function setData(array $data, $validate = true)
    {
        if ($validate && !$this->isValid($data)) {
            throw new \Engine\Exception('Data invalid');
        }
        $this->_data = $data;

        return $this;
    }

    /**
     * Return form element value
     *
     * @param string $name
     * @param object $entity
     * @param array $data
     *
     * @return mixed
     */
    /*public function getCustomValue($name, $entity, $data)
    {
        if (is_array($data) && array_key_exists($name, $data)) {
            return $data[$name];
        }
        if (is_array($this->_data) && array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }
        return null;
    }*/

}