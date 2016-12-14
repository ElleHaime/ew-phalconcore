<?php
/**
 * @namespace
 */
namespace Engine\Crud\Helper\Grid;

/**
 * Class html grid helper
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Helper
 */
class Standart extends \Engine\Crud\Helper
{
	/**
	 * Generates a widget to show a html grid
	 *
	 * @param \Engine\Crud\Grid $grid
	 * @return string
	 */
	static public function _(\Engine\Crud\Grid $grid)
	{
		$title = $grid->getTitle();
		$code = '';

		if ($title) {
			$code .= '<h3>'.$grid->getTitle().'</h3>';
		}
        $code = '        
        <table id="'.$grid->getId().'" autowidth="true" class="'.$grid->getAttrib('class').' table table-bordered table-hover table-striped">';

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
        </table>';
    }
}