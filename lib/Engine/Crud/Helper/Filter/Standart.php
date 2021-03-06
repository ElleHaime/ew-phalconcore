<?php
/**
 * @namespace
 */
namespace Engine\Crud\Helper\Filter;

use Engine\Crud\Grid\Filter;

/**
 * Class grid filter helper
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Helper
 */
class Standart extends \Engine\Crud\Helper
{
	/**
	 * Generates a widget to show a html grid filter
	 *
	 * @param \Engine\Crud\Grid\Filter $filter
	 * @return string
	 */
	static public function _(Filter $filter)
	{
        $filter->initForm();
        $form = $filter->getForm();
        $code = '<form method="'.$filter->getMethod().'" action="'.$filter->getAction().'" class="navbar-form navbar-left">';
        $code .= " <div class=\"form-group\">
            <legend>".$filter->getTitle()."</legend>
            ";

		return $code;
	}

    /**
     * Crud helper end tag
     *
     * @return string
     */
    static public function endTag()
    {
        return '
        </div></form>';
    }
}