<?php
/**
 * @namespace
 */
namespace Engine\Crud\Grid;

use Engine\Forms\Form,
    Engine\Crud\Grid\Filter\FieldInterface,
    Engine\Crud\Grid\Filter\Field;

/**
 * Class filter grid.
 *
 * @uses       \Engine\Crud\Grid\Exception
 * @uses       \Engine\Crud\Grid\Filter
 * @uses       \Engine\Crud\Grid\Field
 * @category   Engine
 * @package    Crud
 * @subpackage Grid
 */
class Filter
{	
	use \Engine\Tools\Traits\Resource,
		\Engine\Crud\Tools\Renderer,
        \Engine\Crud\Tools\Attributes;

    /**
     * Default decorator
     */
    const DEFAULT_DECORATOR = 'standart';

	/**
	 * Phalcon form
	 * @var \Engine\Forms\Form
	 */
	protected $_form;

    /**
     * Crud grid
     * @var \Engine\Crud\Grid
     */
    protected $_grid;

    /**
     * Filter title
     * @var string
     */
    protected $_title = 'Filters';

	/**
	 * Filter fields
	 * @var array
	 */
	protected $_fields = [];
	
	/**
	 * Form fields names
	 * @var array
	 */
	protected $_fieldNames = [];

	/**
	 * Filter action prefix
	 * @var string
	 */
	protected $_prefix;

	/**
	 * Data container object
	 * @var \Engine\Crud\Container\AbstractContainer
	 */
	protected $_container;
	
	/**
	 * Filter params
	 * @var array
	 */
	protected $_params = [];

    /**
     * Hashed params
     * @var string
     */
    protected $_hashParams;
	
	/**
	 * Form action
	 * @var string
	 */
	protected $_action;
	
	/**
	 * Action method
	 * @var string
	 */
	protected $_method;
		
	/**
	 * Form created flag
	 * @var bool
	 */
	protected $_formCreated = false;
	
	/**
	 * Multi form flag
	 * @var bool
	 */
	protected $_multi = false;

    /**
     * Primary field object
     * @var \Engine\Crud\Grid\Filter\Field\Primary
     */
    protected $_primaryField;
	
	/**
     * Constructor
     *
     * Registers filter form
     *
     * @param array $fieds
     * @return void
     */
    public function __construct(array $fields = [], $prefix = null, $method = 'post')
    {
		$this->_initResource();
		$this->_prefix = $prefix;
		if (count($fields) > 0) {
			$this->addFields($fields);
		}
        $this->_method = $method;
	}

    /**
     * Initialaize filter
     *
     * @param \Engine\Crud\Grid $grid
     * @return void
     */
    public function init(\Engine\Crud\Grid $grid)
    {
        $this->_grid = $grid;
        $this->_autoloadInitMethods();

        foreach ($this->_fields as $key => $field) {
            $field->init($this, $key);
            if ($field instanceof Field\Primary) {
                $this->_primaryField = $field;
            }
        }
    }

    /**
     * Autoload all methods in class with prefix in function name _init
     *
     * @return void
     */
    private function _autoloadInitMethods()
    {
        $this->setAutoloadMethodPrefix('_init');
        $this->_runResourceMethods();
    }

    /**
     * Initialize decorator
     *
     * @return void
     */
    protected function _initDecorator()
    {
        $this->_decorator = static::DEFAULT_DECORATOR;
    }

    /**
     * Do something before render
     *
     * @return string
     */
    protected function _beforeRender()
    {
    }
	
    /**
     * Add multiple elements at once
     *
     * @param  array $elements
     * @return \Engine\Crud\Grid\Filter
     */
    public function addFields(array $fields)
    {
        foreach ($fields as $key => $spec) {
            $name = null;
            if (!is_numeric($key)) {
                $name = $key;
            }

            if (is_string($spec) || ($spec instanceof FieldInterface)) {
                $this->addField($spec, $key);
                continue;
            }

            if (is_array($spec)) {
                $argc = count($spec);
                $options = [];
                if (isset($spec['type'])) {
                    $type = $spec['type'];
                	if (isset($spec['key'])) {
                        $key = $spec['key'];
                    }
                    if (isset($spec['name'])) {
                        $name = $spec['name'];
                    }
                    if (isset($spec['options'])) {
                        $options = $spec['options'];
                    }
                    $this->addField($type, $key, $options);
                } else {
                    switch ($argc) {
                        case 0:
                            continue;
                        case (1 <= $argc):
                            $type = array_shift($spec);
                        case (2 <= $argc):
                            if (null === $name) {
                                $name = array_shift($spec);
                            } else {
                                $options = array_shift($spec);
                            }
                        case (3 <= $argc):
                            if (empty($options)) {
                                $options = array_shift($spec);
                            }
                        default:
                           $this->addField($type, $key, $options);
                    }
                }
            }
        }
        
        return $this;
    }
	
   /**
     * Create an field
     *
     * @param  string $type
     * @param  string $key
     * @param  array $options
     * @return \Engine\Crud\Grid\Filter\Field
     */
    public function createField($type, $key, $options = null)
    {
        if (!is_string($type)) {
            throw new \Engine\Exception('Field type must be a string indicating type');
        }

        if (!is_string($key)) {
            throw new \Engine\Exception('Field name must be a string');
        }
        $class = $this->getFieldClass($type);
		$rc = new \ReflectionClass($class);
		$field = $rc->newInstanceArgs($options);
		
        return $field;
    }
    
	/**
	 * Return filter field class name
	 * 
	 * @param string $type
	 * @return string
	 */
	public function getFieldClass($type)
	{
		return '\Engine\Crud\Grid\Filter\Field\\'.ucfirst($type);
	}
    
    /**
     * Add new field
     * 
     * @param \Engine\Crud\Grid\Filter\Field $field
     * @param string $key
     * @param array $options
     *
     * @return \Engine\Crud\Grid\Filter
     */
    public function addField($field, $key = null, $options = null)
    {
    	if (is_string($field)) {
    		if (null === $key) {
                throw new \Engine\Exception('Fields specified by string must have an accompanying name');
            }
    		$field = $this->createField($field, $key, $options);
    	}
    	if (is_numeric($key) || is_null($key)) {
			$key = $field->getName();
		}
        if ($field instanceof Field\Primary) {
            if ($this->_primaryField) {
                throw new \Engine\Exception("Primary field already exists '".$this->_primaryField->getKey()."'");
            }
            $this->_primaryField = $field;
        }
		$this->_fields[$key] = $field;

        return $this;
    }

    /**
     * Return filter fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->_fields;
    }

    /**
     * Return if exists Field by form field key
     *
     * @param string $name
     * @return \Engine\Crud\Grid\Filter\Field
     */
    public function getFieldByKey($key)
    {
        if (isset($this->_fields[$key])) {
            return $this->_fields[$key];
        }

        return false;
    }

    /**
     * Return if exists form field by field name
     *
     * @param string $name
     * @return \Engine\Crud\Grid\Filter\Field
     */
    public function getFieldByName($name)
    {
        foreach ($this->_fields as $key => $field) {
            $c_name = $field->getName();
            if ($c_name === $name) {
                return $field;
            }
        }

        return false;
    }

    /**
     * Return if exists field key by name
     *
     * @param string $name
     * @return string
     */
    public function getFieldKeyByName($name)
    {
        if ($field = $this->getFieldByName($name)) {
            return $field->getKey();
        }

        return false;
    }

    /**
     * Return if exists field name by key
     *
     * @param string $key
     * @return string
     */
    public function getFieldNameByKey($key)
    {
        if (isset($this->_fields[$key])) {
            return $this->_fields[$key]->getName();
        }

        return false;
    }

    /**
     * Return if exist primary grid filter field
     *
     * @return array
     */
    public function getPrimaryField()
    {
        return $this->_primaryField;
    }

    /**
     * Return grid
     *
     * @return \Engine\Crud\Grid
     */
    public function getGrid()
    {
        return $this->_grid;
    }
    
    /**
     * Set grid container adapter
     * 
     * @param \Engine\Crud\Container\AbstractContainer $container
     * @return \Engine\Crud\Grid\Filter
     */
    public function setContainer(\Engine\Crud\Container\AbstractContainer $container)
    {
    	$this->_container = $container;
    	return $this;
    }
    
    /**
     * Return grid container adapter
     *
     * @return \Engine\Crud\Container\AbstractContainer
     */
    public function getContainer()
    {
    	return $this->_container;
    }
	
    /**
     * Set params
     * 
     * @param array $params
     * @return \Engine\Crud\Grid\Filter
     */
    public function setParams(array $params) 
    {
        if (!$this->checkHashParams($params)) {
            return $this;
        }

        foreach ($this->_fields as $key => $field) {
            if (!$field instanceof FieldInterface) {
                throw new \Engine\Exception("Filter field '".$key."' not instance of FieldInterface");
            }
            if ($field instanceof Field\Compound) {
                $field->setValue($this->_params);
            } else {
                $value = (array_key_exists($key, $this->_params)) ? $this->_params[$key] : false;
                $field->setValue($value);
            }
        }

        return $this;
    }

    /**
     * Nulled filter params
     *
     * @return \Engine\Crud\Grid\Filter
     */
    public function clearParams()
    {
        $this->_params = null;
        $this->_hashParams = null;
        foreach ($this->_fields as $key => $field) {
            $field->clearParam();
        }

        return $this;
    }

    /**
     * Check if params was already set
     *
     * @param array $params
     * @return bool
     */
    public function checkHashParams(array $params)
    {
        if ($this->_prefix) {
            $params = (isset($params[$this->_prefix])) ? $params[$this->_prefix] : [];
        }
        $hashParams = sha1(serialize($params));
        if ($this->_hashParams === $hashParams) {
            return false;
        }

        $this->_params = $params;
        $this->_hashParams = $hashParams;

        return true;
    }

    /**
     * Return params
     *
     * @return array
     */
    public function getParams()
    {
        $params = [];
        foreach ($this->_fields as $key => $field) {
            if ($field instanceof Field\Submit) {
                continue;
            }
            $value = $field->getValue();
            if (
                $value === false ||
                $value === null ||
                $value === ''
            ) {
                continue;
            }
            if (is_array($value)) {
                if (isset($value[0])) {
                    $params[$key] = $value;
                } else {
                    $params = array_merge($params, $value);
                }
            } else {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * Set filter param
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setParam($key, $value)
    {
        if (!$this->checkHashParam($key, $value)) {
            return $this;
        }

        $field = $this->getFieldByKey($key);
        if ($field) {
            $field->setValue($value);
        }

        return $this;
    }

    /**
     * Check if param was already set
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function checkHashParam($key, $value)
    {
        if (isset($this->_params[$key]) && $this->_params[$key] == $value) {
            return false;
        }
        $this->_params[$key] = $value;
        $this->_hashParams = sha1(serialize($this->_params));

        return true;
    }
    
	/**
	 * Apply filters to grid data source object.
	 * 
	 * @param object $dataSource
	 * @return \Engine\Crud\Grid\Filter
	 */
	public function applyFilters($dataSource)
	{
		foreach ($this->_fields as $key => $field) {
			$field->applyFilter($dataSource, $this->_container);
		}

		return $this;
	}
	
	/**
	 * Initialize form elements
	 * 
	 * @return \Engine\Crud\Grid\Filter
	 */
	public function initForm()
	{
		if ($this->_formCreated) {
			return $this;
		}
		
		$this->_form = new Form();
		
		if ($this->_multi) {
			$prefix = ($this->_prefix) ? $this->_prefix."[1]" : $prefix = "[1]";
		} else {
			$prefix = $this->_prefix;
		}
		
		$this->_fieldNames = [];
    	foreach ($this->_fields as $key => $field) {
            if (!$field instanceof FieldInterface) {
                throw new \Engine\Exception("Filter field '".$key."' not instance of FieldInterface");
            }
            if ($field instanceof Field\Compound) {
                $field->initForm($this->_form);
            } else {
                $elements = $field->getElement();
                $field->updateField();
                if (!is_array($elements)) {
                    $elements = [$elements];
                }
                foreach ($elements as $element) {
                    $name = $prefix."[".$key."]";
                    $this->_fieldNames[$name] = $field->getName();
                    $this->_form->add($element);
                }
            }
    	}

    	$this->_form->setAction($this->_action);
        $this->_form->setMethod($this->_method);
    	$this->_formCreated = true;
    	
    	return $this;
	}
	
	/**
	 * Return phalcon form object
	 * 
	 * @return \Engine\Forms\Form
	 */
	public function getForm()
	{
		return $this->_form;
	}

    /**
     * Set filter title
     *
     * @param string $title
     * @return string
     */
    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    /**
     * Return filter title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }


    /**
     * Return filter action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * Return filter form method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }
	
	/**
	 * Return filter params prefix
	 * 
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->_prefix;
	}

	/**
     * Validate the form
     *
     * @param array $params
     * @return boolean
     */
	public function isValid($params)
	{
		if ($this->_formCreated === false) {
	        throw new \Engine\Exception('Form not created!');
	    }
	    
	    return $this->_form->isValid($params);
	}
	
	/**
	 * Return messages generated by form field validators
	 * 
	 * @return array
	 */
	public function getMessages()
	{
		if ($this->_formCreated === false) {
	        throw new \Engine\Exception('Form not created!');
	    }
	    
	    return $this->_form->getMessages();
	}
	
    /**
     * Return filter field
     *
     * @param  string $key The filter field key.
     * @return \Engine\Crud\Grid\Filter\Field
     * @throws \Exception if the $key is not a field in the filter.
     */
    public function __get($key)
    {
        if (!isset($this->_fields[$key])) {
            throw new \Engine\Exception("Field \"$key\" is not in the filter");
        }
        return $this->_fields[$key];
    }
}