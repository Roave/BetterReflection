<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\SourceLocator\Type\FileIteratorSourceLocator;
use Rector\BetterReflectionTest\Assets\DirectoryScannerAssets;
use Rector\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Type\FileIteratorSourceLocator
 */
class FileIteratorSourceLocatorTest extends TestCase
{
    /**
     * @var FileIteratorSourceLocator
     */
    private $sourceLocator;

    /**
     * {@inheritDoc}
     */
    public function setUp() : void
    {
        parent::setUp();

        $this->sourceLocator = new FileIteratorSourceLocator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                __DIR__ . '/../../Assets/DirectoryScannerAssets',
                RecursiveDirectoryIterator::SKIP_DOTS
            )),
            BetterReflectionSingleton::instance()->astLocator()
        );
    }

    public function testScanDirectoryClasses() : void
    {
        $classes = $this->sourceLocator->locateIdentifiersByType(
            new ClassReflector($this->sourceLocator),
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        );

        self::assertCount(2, $classes);

        $classNames = \array_map(
            function (ReflectionClass $reflectionClass) : string {
                return $reflectionClass->getName();
            },
            $classes
        );

        \sort($classNames);

        self::assertEquals(DirectoryScannerAssets\Bar\FooBar::class, $classNames[0]);
        self::assertEquals(DirectoryScannerAssets\Foo::class, $classNames[1]);
    }

    public function testLocateIdentifier() : void
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
