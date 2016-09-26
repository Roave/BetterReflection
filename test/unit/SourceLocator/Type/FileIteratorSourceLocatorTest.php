<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Type\FileIteratorSourceLocator;
use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use BetterReflectionTest\Assets\DirectoryScannerAssets;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @covers \BetterReflection\SourceLocator\Type\FileIteratorSourceLocator
 */
class FileIteratorSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileIteratorSourceLocator
     */
    private $sourceLocator;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->sourceLocator = new FileIteratorSourceLocator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                __DIR__ . '/../../Assets/DirectoryScannerAssets',
                RecursiveDirectoryIterator::SKIP_DOTS
            ))
        );
    }

    public function testScanDirectoryClasses()
    {
        $classes = $this->sourceLocator->locateIdentifiersByType(
            new ClassReflector($this->sourceLocator),
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        );

        self::assertCount(2, $classes);

        $classNames = array_map(
            function (ReflectionClass $reflectionClass) {
                return $reflectionClass->getName();
            },
            $classes
        );

        sort($classNames);

        self::assertEquals(DirectoryScannerAssets\Bar\FooBar::class, $classNames[0]);
        self::assertEquals(DirectoryScannerAssets\Foo::class, $classNames[1]);
    }

    public function testScanDirectoryFiles()
    {
        $fileSystemIteratorSourceLocator = $this->sourceLocator;
        $class = new \ReflectionClass(get_class($fileSystemIteratorSourceLocator));
        $method = $class->getMethod('scan');
        $method->setAccessible(true);
        $result = $method->invoke($fileSystemIteratorSourceLocator);
        self::assertCount(3, $result);

        // test file path
        $files = [];
        foreach ($result as $file) {
            $class = new \ReflectionClass('BetterReflection\SourceLocator\Type\SingleFileSourceLocator');
            $property = $class->getProperty('filename');
            $property->setAccessible(true);
            $files[] = realpath($property->getValue($file));
        }
        sort($files);
        self::assertEquals(realpath(__DIR__ . '/../../Assets/DirectoryScannerAssets/Bar/Empty.php'), $files[0]);
        self::assertEquals(realpath(__DIR__ . '/../../Assets/DirectoryScannerAssets/Bar/FooBar.php'), $files[1]);
        self::assertEquals(realpath(__DIR__ . '/../../Assets/DirectoryScannerAssets/Foo.php'), $files[2]);

        // test class names
        $classNames = [];
        foreach ($result as $file) {
            /* @var $file SingleFileSourceLocator */
            $reflector = new ClassReflector($file);
            foreach ($reflector->getAllClasses() as $class) {
                $classNames[] = $class->getName();
            }
        }
        sort($classNames);
        self::assertEquals('BetterReflectionTest\Assets\DirectoryScannerAssets\Bar\FooBar', $classNames[0]);
        self::assertEquals('BetterReflectionTest\Assets\DirectoryScannerAssets\Foo', $classNames[1]);
        self::assertCount(2, $classNames);
    }
}
