<?php
/**
 * @namespace
 */
namespace Engine\UnitTest\Generator;

/**
 * Generator for base test class skeletons from classes.
 *
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 */
class BaseTestGenerator extends AbstractGenerator
{
    /**
     * Constructor.
     *
     * @param string $outClassName
     * @param string $outSourceFile
     * @param string $baseNamespace
     * @throws \RuntimeException
     */
    public function __construct($outClassName = '', $outSourceFile = '', $baseNamespace = '')
    {
        $this->_baseNamespace = $baseNamespace;

        $this->_outClassName = $this->_parseFullyQualifiedClassName(
            $outClassName
        );

        $this->_outSourceFile = str_replace(
            $this->_outClassName['fullyQualifiedClassName'],
            $this->_outClassName['className'],
            $outSourceFile
        );
    }

    /**
     * Generate base test class
     *
     * @return string
     */
    public function generate()
    {

        $classTemplate = new Template(
            sprintf(
                '%s%stemplate%sTestBaseClass.tpl',
                __DIR__,
                DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR
            )
        );

        $classTemplate->setVar(
            [
                'namespace'          => trim($this->_outClassName['namespace'], "\\"),
                'testClassName'      => $this->_outClassName['className'],
                'date'               => date('Y-m-d'),
                'time'               => date('H:i:s')
            ]
        );

        return $classTemplate->render();
    }
}
