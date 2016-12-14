<?php
/**
 * @namespace
 */
namespace Engine\Crud\Form\Field;

use Engine\Crud\Form\Field;

/**
 * Text field
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Form
 */
class Security extends Field
{
    /**
     * Form element type
     * @var string
     */
    protected $_type = 'hidden';

    /**
     * is save field value
     * @var bool
     */
    protected $_notSave = true;

    /**
     * Security object
     * @var \Phalcon\Security
     */
    private $_security;

    /**
     * Session object
     * @var \Phalcon\Session
     */
    private $_session;

    /**
     * Previous token from session
     * @var string
     */
    private $_sessionToken;

    /**
     * Previous token key from session
     * @var string
     */
    private $_sessionTokenKey;
	
	/**
	 * Constructor 
	 *
     * @param \Phalcon\Security $security
	 */
	public function __construct(\Phalcon\Security $security = null, \Phalcon\Session $session = null)
    {
        parent::__construct();
        $this->_security = $security;
        $this->_session = $session;
	}

    /**
     * Initialize field (used by extending classes)
     *
     * @return void
     */
    protected function _init()
    {
        parent::_init();

        if (!$this->_security) {
            $dependencyInjection = $this->getDi();
            $this->_security = $dependencyInjection->get('security');
            $this->_session = $dependencyInjection->get('session');
        }

        $this->_sessionToken = $this->_security->getSessionToken();
        $this->_sessionTokenKey = $this->_session->get('$PHALCON/CSRF/KEY$');

        $this->_name = $this->_security->getTokenKey();
        $this->_default = $this->_security->getToken();
        //$this->_value = $this->_default;

    }

    /**
     * Validate token
     *
     * @param array $data
     *
     * @return bool
     */
    public function isValid(array $data)
    {
        if (
            array_key_exists($this->_sessionTokenKey, $data) &&
            $this->_sessionToken === $data[$this->_sessionTokenKey]
        ) {
            return true;
        }
        
        return false;
    }

    /**
     * Create phalcon from element
     *
     * @throws \Engine\Exception
     * @return void
     */
    protected function _createElement()
    {
        $key = $this->_key;
        $this->_key = $this->_name;
        parent::_createElement();
        $this->_key = $key;
    }

}