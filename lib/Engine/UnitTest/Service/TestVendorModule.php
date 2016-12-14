<?php
/**
 * @namespace
 */
namespace Engine\UnitTest\Service;

use Engine\UnitTest\Generator\TestGenerator,
    Engine\UnitTest\Generator\BaseTestGenerator,
    Engine\UnitTest\Generator\RegularTestGenerator;

/**
 * Class TestProject
 *
 * @package UnitTest
 * @subpackage Service
 */
class TestVendorModule extends TestClass
{
    /**
     * Base test namespace
     * @var string
     */
    protected $_baseNamespace = false;

    /**
     * Generate tests
     *
     * @return void
     * @throws \Exception
     */
    public function generate()
    {
        if (!file_exists($this->_sourcePath)) {
            throw new \Engine\Exception("Source file '{$this->_sourcePath}' doesn't exitsts!");
        }
        $this->_generateBase($this->_sourcePath);
        $this->_scanSources($this->_sourcePath);

        $path = explode('\\', trim(str_replace("/", "\\", $this->_sourcePath), "/"));
        $namespace = end($path);
        $modules[$namespace] = $this->_localTestsPath;
        $this->_generateRegular($this->_sourcePath, 'bootstrap.php', $modules);
        $this->_generateRegular($this->_sourcePath, 'phpunit.xml', $modules);
    }

    /**
     * Scan all subfolders in the source project directory and generate test for all classes
     *
     * @param string $directory
     * @return void
     */
    protected function _scanSources($sourcePath)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourcePath , \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }
            if ($item->getExtension() != 'php') {
                continue;
            }
            $this->_generateTest($item);
        }
    }

    /**
     * Generate skeleton for base class
     *
     * @param string $sourcePath
     * @return boolean
     * @throws \Exception
     */
    protected function _generateBase($sourcePath)
    {
        $tmpPath = explode('\\', trim(str_replace("/", "\\", $sourcePath), "/"));
        $namespace = end($tmpPath);
        list($testPath, $localTestPath) = $this->_getTestPathFromSource($sourcePath, $namespace);
        if (!file_exists($testPath)) {
            if (!@mkdir($testPath, 0755, true) && !is_dir($testPath)) {
                $mkdirErrorArray = error_get_last();
                throw new \Engine\Exception('Directory \''.$testPath.'\' was not created!');
            }
        }
        $testNamespace = $namespace.'Test';
        if ($this->_baseNamespace) {
            $testNamespace = $this->_baseNamespace.'\\'.$testNamespace;
        }

        $generator = new BaseTestGenerator(
            '\\'.$testNamespace.'\\Base',
            $testPath.'/Base.php',
            $this->_baseNamespace
        );
        $generator->write();

        return true;
    }

    /**
     * Generate skeleton for regular file
     *
     * @param string $sourcePath
     * @param string $filename
     * @param array $modules
     *
     * @return boolean
     * @throws \Exception
     */
    protected function _generateRegular($sourcePath, $filename, array $modules)
    {
        $tmpPath = explode('\\', trim(str_replace("/", "\\", $sourcePath), "/"));
        $namespace = array_pop($tmpPath);
        if (end($tmpPath) == 'lib') {
            array_pop($tmpPath);
        }
        $tmpPath[] = 'tests';
        $testPath = implode('/', $tmpPath);
        if (!file_exists($testPath)) {
            if (!@mkdir($testPath, 0755, true) && !is_dir($testPath)) {
                $mkdirErrorArray = error_get_last();
                throw new \Engine\Exception('Directory \''.$testPath.'\' was not created!');
            }
        }

        $generator = new RegularTestGenerator(
            '\\'.$namespace,
            $testPath.'/'.$filename
        );
        $generator->write();

        return true;
    }

}