<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use BetterReflectionTest\Assets\DirectoryScannerAssets;
use BetterReflectionTest\Assets\DirectoryScannerAssetsFoo;

/**
 * @covers \BetterReflection\SourceLocator\Type\DirectoriesSourceLocator
 */
class DirectoriesSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DirectoriesSourceLocator
     */
    private $sourceLocator;

    public function setUp()
    {
        $this->sourceLocator = new DirectoriesSourceLocator([
            __DIR__ . '/../../Assets/DirectoryScannerAssets',
            __DIR__ . '/../../Assets/DirectoryScannerAssetsFoo',
        ]);
    }

    public function testScanDirectoryClasses()
    {
        $classes = $this->sourceLocator->locateIdentifiersByType(
            new ClassReflector($this->sourceLocator),
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        );

        $this->assertCount(4, $classes);

        $classNames = array_map(
            function (ReflectionClass $reflectionClass) {
                return $reflectionClass->getName();
            },
            $classes
        );

        sort($classNames);

        $this->assertEquals(DirectoryScannerAssetsFoo\Bar\FooBar::class, $classNames[0]);
        $this->assertEquals(DirectoryScannerAssetsFoo\Foo::class, $classNames[1]);
        $this->assertEquals(DirectoryScannerAssets\Bar\FooBar::class, $classNames[2]);
        $this->assertEquals(DirectoryScannerAssets\Foo::class, $classNames[3]);
    }
}
