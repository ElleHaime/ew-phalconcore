<?php
/**
 * @namespace
 */
namespace Engine\UnitTest\Service;

use Engine\UnitTest\Generator\TestGenerator;

/**
 * Class TestClass
 *
 * @package UnitTest
 * @subpackage Service
 */
class TestClass
{
    /**
     * Base test namespace
     * @var string
     */
    protected $_baseNamespace = false;

    /**
     * Source path
     * @var string
     */
    protected $_sourcePath;

    /**
     * Local path for generated tests
     * @var array
     */
    protected $_localTestsPath = [];

    /**
     * Constructor
     * @param string $sourcePath
     */
    public function __construct($sourcePath)
    {
        $this->_sourcePath = $sourcePath;
    }

    /**
     * Generate test
     *
     * @return void
     * @throws \Exception
     */
    public function generate()
    {
        if (!file_exists($this->_sourcePath)) {
            throw new \Exception("Source '{$this->_sourcePath}' doesn't exists!");
        }
        $item = new \SplFileInfo($this->_sourcePath);
        $this->_generateTest($item);
    }

    /**
     * Generate skeleton for source class
     *
     * @param \FilesystemIterator $item
     * @return boolean
     * @throws \Exception
     */
    protected function _generateTest(\SplFileInfo $item)
    {
        $sourcePath = $item->getPath();
        $sourceFileName = $item->getFilename();
        $sourceClassName = $this->_getClassName($sourcePath.'/'.$sourceFileName);
        if (!$sourceClassName) {
            return false;
        }
        list($testPath, $localTestPath) = $this->_getTestPathFromSource($sourcePath, $sourceClassName['namespace']);

        if (!file_exists($testPath)) {
            if (!@mkdir($testPath, 0755, true) && !is_dir($testPath)) {
                $mkdirErrorArray = error_get_last();
                throw new \Engine\Exception('Directory \''.$testPath.'\' was not created!');
            }
        }

        if ($sourceClassName['class'] === 'Module') {
            echo $sourceFileName.' is a mvc module, will be excluded'.PHP_EOL;
            return false;
        }
        $tmp = explode('\\', $sourceClassName['namespace']);
        if (end($tmp) === 'Controller') {
            echo $sourceFileName.' is a mvc controller, will be excluded'.PHP_EOL;
            return false;
        }
        if (end($tmp) === 'Task') {
            echo $sourceFileName.' is a cli task, will be excluded'.PHP_EOL;
            return false;
        }

        $testNamespace = [];
        if (count($tmp) > 1) {
            foreach ($tmp as $i => $v) {
                if ($i == 0) {
                    $v .= 'Test';
                }
                $testNamespace[] = $v;
            }
        } else {
            $tmp[0] .= 'Test';
            $testNamespace = $tmp;
        }
        if ($this->_baseNamespace) {
            array_unshift($testNamespace, $this->_baseNamespace);
        }
        $testNamespace = implode('\\', $testNamespace);

        $generator = new TestGenerator(
            '\\'.$sourceClassName['namespace'].'\\'.$sourceClassName['class'],
            $sourcePath.'/'.$sourceFileName,
            '\\'.$testNamespace.'\\'.$sourceClassName['class'],
            $testPath.'/'.$sourceFileName,
            $this->_baseNamespace
        );
        $generator->write();
        $this->_localTestsPath[] = $localTestPath.'/'.$sourceFileName;

        return true;
    }

    /**
     * Generate test folder for new tests
     *
     * @param string $sourcePath
     * @param string $namespace
     * @return string
     */
    protected function _getTestPathFromSource($sourcePath, $namespace = '')
    {
        $namespace = trim(str_replace('\\', '/', $namespace), '/');
        $moduleDir = false;
        if (in_array($namespace, ['module', 'modules'])) {
            $namespace = '';
        }
        if ($namespace) {
            $sourcePath = trim(str_replace('\\', '/', str_replace($namespace, '', $sourcePath)), '/');
        }
        $treePath = explode('/', trim($sourcePath, '/'));
        $testPath = '';
        $localTestPath = '';
        $step = 1;

        $parentFolder = array_reverse($treePath)[0];
        if (in_array($parentFolder, ['module', 'modules'])) {
            $moduleDir = $parentFolder;
            $tmpParentFolder = array_reverse($treePath)[1];
            if (in_array($tmpParentFolder, ['apps', 'app'])) {
                $parentFolder = $tmpParentFolder;
                $step = 2;
            }
        }

        if (in_array($parentFolder, ['lib', 'apps', 'app'])) {
            $testPath = implode('/', array_slice($treePath, 0, count($treePath)-$step));
            $testPath .= '/tests';
            if ($namespace) {
                $namespace = explode('/', $namespace);
                $namespace[0] .= 'Test';
                $testPath .= '/'.$parentFolder;
                $localTestPath .= $parentFolder;
                if ($moduleDir) {
                    $testPath .= '/'.$moduleDir;
                    $localTestPath .= '/'.$moduleDir;
                }
                $testPath .= '/'.implode('/', $namespace);
                $localTestPath .= '/'.implode('/', $namespace);
            } else {
                $testPath .= '/'.$parentFolder;
                $localTestPath .= $parentFolder;
                if ($moduleDir) {
                    $testPath .= '/'.$moduleDir;
                    $localTestPath .= '/'.$moduleDir;
                }
            }
        } else {
            $testPath = implode('/', $treePath);
            $testPath .= '/tests';
            if ($moduleDir) {
                $testPath .= '/'.$moduleDir;
                $localTestPath .= $moduleDir;
            }
        }

        return ['/'.$testPath, $localTestPath];
    }

    /**
     * Analaze source path and return namespace and origin class name
     *
     * @param string $sourceFilePath
     * @return array
     * @throws \Exception
     */
    protected function _getClassName($sourceFilePath)
    {
        $namespace = 0;
        $tokens = token_get_all(file_get_contents($sourceFilePath));
        $count = count($tokens);
        $dlm = false;
        $class = [];
        for ($i = 2; $i < $count; $i++) {
            if ((isset($tokens[$i - 2][1]) && ($tokens[$i - 2][1] == 'phpnamespace' || $tokens[$i - 2][1] == 'namespace')) ||
                ($dlm && $tokens[$i - 1][0] == T_NS_SEPARATOR && $tokens[$i][0] == T_STRING))
            {
                if (!$dlm) {
                    $namespace = 0;
                }
                if (isset($tokens[$i][1])) {
                    $namespace = $namespace ? $namespace . "\\" . $tokens[$i][1] : $tokens[$i][1];
                    $dlm = true;
                }
            } elseif ($dlm && ($tokens[$i][0] != T_NS_SEPARATOR) && ($tokens[$i][0] != T_STRING)) {
                $dlm = false;
            }
            if (($tokens[$i - 2][0] == T_CLASS || (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] == "phpclass"))
                && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING)
            {
                $class['namespace'] = $namespace;
                $class['class'] = $tokens[$i][1];
            }
        }

        return $class;
    }
}