<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Type\FileIteratorSourceLocator;
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

    public function testLocateIdentifier()
    {
        $class = $this->sourceLocator->locateIdentifier(
            new ClassReflector($this->sourceLocator),
            new Identifier(
                DirectoryScannerAssets\Bar\FooBar::class,
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        );

        self::assertInstanceOf(ReflectionClass::class, $class);
        self::assertSame(DirectoryScannerAssets\Bar\FooBar::class, $class->getName());
    }
}
