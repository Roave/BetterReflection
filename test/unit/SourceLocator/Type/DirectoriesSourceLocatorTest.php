<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Exception\InvalidDirectory;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflectionTest\Assets\DirectoryScannerAssets;
use Roave\BetterReflectionTest\Assets\DirectoryScannerAssetsFoo;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function array_map;
use function sort;
use function uniqid;

#[CoversClass(DirectoriesSourceLocator::class)]
class DirectoriesSourceLocatorTest extends TestCase
{
    private DirectoriesSourceLocator $sourceLocator;

    public function setUp(): void
    {
        parent::setUp();

        $this->sourceLocator = new DirectoriesSourceLocator(
            [
                __DIR__ . '/../../Assets/DirectoryScannerAssets',
                __DIR__ . '/../../Assets/DirectoryScannerAssetsFoo',
            ],
            BetterReflectionSingleton::instance()->astLocator(),
        );
    }

    public function testScanDirectoryClasses(): void
    {
        /** @var list<ReflectionClass> $classes */
        $classes = $this->sourceLocator->locateIdentifiersByType(
            new DefaultReflector($this->sourceLocator),
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
        );

        self::assertCount(4, $classes);

        $classNames = array_map(
            static fn (ReflectionClass $reflectionClass): string => $reflectionClass->getName(),
            $classes,
        );

        sort($classNames);

        self::assertEquals(DirectoryScannerAssetsFoo\Bar\FooBar::class, $classNames[0]);
        self::assertEquals(DirectoryScannerAssetsFoo\Foo::class, $classNames[1]);
        self::assertEquals(DirectoryScannerAssets\Bar\FooBar::class, $classNames[2]);
        self::assertEquals(DirectoryScannerAssets\Foo::class, $classNames[3]);
    }

    public function testLocateIdentifier(): void
    {
        $class = $this->sourceLocator->locateIdentifier(
            new DefaultReflector($this->sourceLocator),
            new Identifier(
                DirectoryScannerAssets\Bar\FooBar::class,
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
            ),
        );

        self::assertInstanceOf(ReflectionClass::class, $class);
        self::assertSame(DirectoryScannerAssets\Bar\FooBar::class, $class->getName());
    }

    /** @param list<string> $directories */
    #[DataProvider('invalidDirectoriesProvider')]
    public function testInvalidDirectory(array $directories): void
    {
        $this->expectException(InvalidDirectory::class);

        new DirectoriesSourceLocator($directories, BetterReflectionSingleton::instance()->astLocator());
    }

    /** @return list<array{0: list<string>}> */
    public static function invalidDirectoriesProvider(): array
    {
        $validDir = __DIR__ . '/../../Assets/DirectoryScannerAssets';

        return [
            [[__DIR__ . '/' . uniqid('nonExisting', true)]],
            [[__FILE__]],
            [[$validDir, __DIR__ . '/' . uniqid('nonExisting', true)]],
            [[$validDir, __FILE__]],
        ];
    }
}
