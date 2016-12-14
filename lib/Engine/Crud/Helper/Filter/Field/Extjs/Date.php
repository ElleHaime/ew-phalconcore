<?php
/**
 * @namespace
 */
namespace Engine\Crud\Helper\Filter\Field\Extjs;

use Engine\Crud\Grid\Filter as Filter,
    Engine\Crud\Grid\Filter\Field as Field;

/**
 * Class filter fields helper
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Helper
 */
class Date extends BaseHelper
{
    /**
     * Render extjs date filter field
     *
     * @param \Engine\Crud\Grid\Filter\Field $field
     * @return string
     */
    public static function _(Field\Date $field)
    {
        $fieldCode = [];

        $fieldCode[] = "xtype: 'datefield'";
        $fieldCode[] = "name: '".$field->getKey()."'";

        $label = $field->getLabel();
        if ($label) {
            $fieldCode[] = "fieldLabel: '".$label."'";
        }
        $desc = $field->getDesc();
        if ($desc) {
            $fieldCode[] = "boxLabel: '".$desc."'";
        }
        $width = $field->getWidth();
        if ($width) {
            $fieldCode[] = "width: ".$width;
        }

        $format = $field->getFormat();
        $minValue = $field->getMinValue();
        $maxValue = $field->getMaxValue();
        $dependencyInjectorsabledDays = false;
        $dependencyInjectorsabledDaysText = false;

        $fieldCode[] = "format: '".$format."'";
        if ($minValue !== null && $minValue !== false) {
            $fieldCode[] = "minValue: '".$minValue."'";
        }
        if ($maxValue !== null && $maxValue !== false) {
            $fieldCode[] = "maxValue: '".$maxValue."'";
        }
        if ($dependencyInjectorsabledDays !== null && $dependencyInjectorsabledDays !== false) {
            $fieldCode[] = "disabledDays: '".$dependencyInjectorsabledDays."'";
        }
        if ($dependencyInjectorsabledDaysText !== null && $dependencyInjectorsabledDaysText !== false) {
            $fieldCode[] = "disabledDaysText: '".$dependencyInjectorsabledDaysText."'";
        }

        return forward_static_call(['self', '_implode'], $fieldCode);
    }
}