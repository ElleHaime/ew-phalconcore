<?php
/**
 * @namespace
 */
namespace Engine\Crud\Helper\Form\Extjs;

use Engine\Crud\Form\Extjs as Form;

/**
 * Class form filter helper
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Helper
 */
class Store extends BaseHelper
{
    /**
     * Is create js file prototype
     * @var boolean
     */
    protected static $_createJs = true;

    /**
     * Generates a widget to show a html form
     *
     * @param \Engine\Crud\Form\Extjs $form
     * @return string
     */
    static public function _(Form $form)
    {
        $title = $form->getTitle();
        $action = $form->getAction();
        $key = $form->getKey();

        $code = "
        Ext.define('".static::getFormStoreName()."', {
            extend: 'Ext.ux.crud.Store',
            alias: 'widget.".static::$_module.ucfirst(static::$_prefix)."Store',
            requires: ['Ext.ux.crud.Proxy', 'Ext.ux.crud.Store'],
            model: '".static::getFormModelName()."',
            pageSize: 1,
            autoLoad: false,
            remoteSort: true,
            proxy: {
                type: 'crudproxy',
                api: {
                    get:     '".$action."/get',
                    update:  '".$action."/update',
                    create:  '".$action."/create',
                    destroy: '".$action."/delete'
                },
                reader: {
                    type: 'json',
                    root: '".$key."',
                    totalProperty: 'results'
                },
                writer: {
                    type: 'json',
                    writeAllFields: false,
                    root: '".$key."'
                }
            }
        });";

        return $code;
    }

    /**
     * Return object name
     *
     * @return string
     */
    public static function getName()
    {
        return static::getFormStoreName();
    }

    /**
     * Crud helper end tag
     *
     * @return string
     */
    static public function endTag()
    {
        return false;
    }

}