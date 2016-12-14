<?php
/**
 * @namespace
 */
namespace Engine\Crud\Helper\Form\Extjs;

use Engine\Crud\Form\Extjs as Form,
    Engine\Crud\Form\Field,
    Engine\Crud\Helper\Form\Extjs;

/**
 * Class extjs grid model helper
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Helper
 */
class Model extends BaseHelper
{
    /**
     * Is create js file prototype
     * @var boolean
     */
    protected static $_createJs = true;

    /**
     * Generates a widget to show a html grid
     *
     * @param \Engine\Crud\Form\Extjs $form
     * @return string
     */
    static public function _(Form $form)
    {
        $code = "
        Ext.define('".static::getFormModelName()."', {
            extend: 'Ext.data.Model',";
            $fields = [];
            $validations = [];
            $primary = false;
            foreach ($form->getFields() as $field) {
                if ($field instanceof Field) {
                    $type = $field->getValueType();
                    if (!method_exists(__CLASS__, '_'.$type)) {
                        throw new \Engine\Exception("Field with type '".$type."' haven't render method in '".__CLASS__."'");
                    }
                    $fieldCode = forward_static_call(['self', '_'.$type], $field);

                    //$validationCode = "{field: '".$field."', type: }";
                    /*
                     * type:
                        presence
                        length
                        inclusion
                        exclusion
                        format
                     */
                    $fields[] = $fieldCode;
                    //$validations[] = $validationCode;

                    if ($field instanceof Field\Primary) {
                        $primary = $field->getKey();
                    }
                }
            }
            $code .= "
            fields: [".implode(",", $fields)."
            ],";

            $code .= "
            validations: [".implode(",", $validations)."]";

            if ($primary !== false) {
                $code .= ",
            idProperty: '".$primary."'";
            }
            $code .= "
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
        return static::getFormModelName();
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

    /**
     * Render string model field type
     *
     * @param \Engine\Crud\Form\Field $field
     * @return string
     */
    public static function _string(Field $field)
    {
        $key = $field->getKey();
        $fieldCode = "
                    {
                        name: '".$key."',
                        type: 'string'
                    }";

        return $fieldCode;
    }

    /**
     * Render date model field type
     *
     * @param \Engine\Crud\Form\Field $field
     * @return string
     */
    public static function _date(Field\Date $field)
    {
        $key = $field->getKey();
        $format = $field->getFormat();
        $fieldCode = "
                    {
                        name: '".$key."',
                        type: 'date',
                        dateFormat: '".$format."',
                    }";

        return $fieldCode;
    }

    /**
     * Render collection field type
     *
     * @param \Engine\Crud\Form\Field $field
     * @return string
     */
    public static function _collection(Field\ArrayToSelect $field)
    {
        return self::_int($field);
    }

    /**
     * Render checkbox field type
     *
     * @param \Engine\Crud\Form\Field $field
     * @return string
     */
    public static function _check(Field\Checkbox $field)
    {
        return self::_int($field);
    }

    /**
     * Render numeric field type
     *
     * @param \Engine\Crud\Form\Field $field
     * @return string
     */
    public static function _int(Field $field)
    {
        $key = $field->getKey();
        $fieldCode = "
                    {
                        name: '".$key."',
                        type: 'int'
                    }";

        return $fieldCode;
    }

    /**
     * Render image field type
     *
     * @param \Engine\Crud\Form\Field $field
     * @return string
     */
    public static function _image(Field\Image $field)
    {
        return self::_string($field);
    }

    /**
     * Render file field type
     *
     * @param \Engine\Crud\Form\Field $field
     * @return string
     */
    public static function _file(Field\File $field)
    {
        return self::_string($field);
    }

}