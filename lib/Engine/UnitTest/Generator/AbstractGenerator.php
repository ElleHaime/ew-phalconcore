<?php
/**
 * @namespace
 */
namespace Engine\UnitTest\Generator;

/**
 * Generator for skeletons.
 *
 * @license http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 */
abstract class AbstractGenerator
{
    /**
     * @var array
     */
    protected $_inClassName;

    /**
     * @var string
     */
    protected $_inSourceFile;

    /**
     * @var array
     */
    protected $_outClassName;

    /**
     * @var string
     */
    protected $_outSourceFile;

    /**
     * Base test namespace
     * @var string
     */
    protected $_baseNamespace;

    /**
     * Constructor.
     *
     * @param string $inClassName
     * @param string $inSourceFile
     * @param string $outClassName
     * @param string $outSourceFile
     * @param string $baseNamespace
     */
    public function __construct(
        $inClassName,
        $inSourceFile = '',
        $outClassName = '',
        $outSourceFile = '',
        $baseNamespace = ''
    ) {
        $this->_baseNamespace = $baseNamespace;

        $this->_inClassName = $this->_parseFullyQualifiedClassName(
            $inClassName
        );

        $this->_outClassName = $this->_parseFullyQualifiedClassName(
            $outClassName
        );

        $this->_inSourceFile = str_replace(
            $this->_inClassName['fullyQualifiedClassName'],
            $this->_inClassName['className'],
            $inSourceFile
        );

        $this->_outSourceFile = str_replace(
            $this->_outClassName['fullyQualifiedClassName'],
            $this->_outClassName['className'],
            $outSourceFile
        );
    }

    /**
     * @return string
     */
    public function getOutClassName()
    {
        return $this->_outClassName['fullyQualifiedClassName'];
    }

    /**
     * @return string
     */
    public function getOutSourceFile()
    {
        return $this->_outSourceFile;
    }

    /**
     * Generates the code and writes it to a source file.
     *
     * @param string $file
     * @return boolean
     */
    public function write($file = '')
    {
        if ($file == '') {
            $file = $this->_outSourceFile;
        }
        if (file_exists($file)) {
            echo "UnitTest '".$file."' already exists.".PHP_EOL;
            return false;
        }

        if (file_put_contents($file, $this->generate())) {
            echo "UnitTest '" . $file . "' created.".PHP_EOL;
            return true;
        }

        return false;
    }

    /**
     * @param  string $className
     * @return array
     */
    protected function _parseFullyQualifiedClassName($className)
    {
        if (strpos($className, '\\') !== 0) {
            $className = '\\'.$className;
        }
        $result = [
            'namespace'               => '',
            'testBaseFullClassName'   => '',
            'className'               => $className,
            'fullyQualifiedClassName' => $className
        ];

        if (strpos($className, '\\') !== false) {
            $tmp                 = explode('\\', $className);
            $result['className'] = $tmp[count($tmp)-1];
            $result['namespace'] = $this->_arrayToName($tmp);

            $testBaseFullClassName = ($this->_baseNamespace) ? $this->_baseNamespace.'\\'.$tmp[2].'\Base' : $tmp[1].'\Base';
            $result['testBaseFullClassName'] = $testBaseFullClassName;
        }


        return $result;
    }

    /**
     * @param  array $parts
     * @return string
     */
    protected function _arrayToName(array $parts)
    {
        $result = '';

        if (count($parts) > 1) {
            array_pop($parts);

            $result = join('\\', $parts);
        }

        return $result;
    }

    /**
     * @return string
     */
    abstract public function generate();
}
