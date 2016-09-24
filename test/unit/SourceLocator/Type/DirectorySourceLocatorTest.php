<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Type\DirectorySourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\Type\DirectorySourceLocator
 */
class DirectorySourceLocatorTest extends \PHPUnit_Framework_TestCase
{

    private $directoryToScan = __DIR__ . '/../../Assets/DirectoryScannerAssets';

    /**
     * @var DirectorySourceLocator
     */
    private $sourceLocator;

    public function setUp()
    {
        $this->sourceLocator = new DirectorySourceLocator([$this->directoryToScan]);
    }

    public function testScanDirectoryClasses()
    {
        $reflector = new ClassReflector($this->sourceLocator);
        $this->assertCount(2, $reflector->getAllClasses());
    }

    public function testScanDirecotryFiels(){
        $class = new \ReflectionClass(get_class($this->sourceLocator));
        $method = $class->getMethod('scan');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->sourceLocator, [$this->directoryToScan]);
        $this->assertCount(3, $result);
    }

    /**
     * @expectedException \BetterReflection\SourceLocator\Exception\InvalidDirectory
     */
    public function testInvalidDirectory(){
        new DirectorySourceLocator([substr($this->directoryToScan,0, strlen($this->directoryToScan)-1)]);
    }
}