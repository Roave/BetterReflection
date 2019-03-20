<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr4Locator;
use Roave\BetterReflectionTest\Assets\DirectoryScannerAssets\Bar\FooBar;
use Roave\BetterReflectionTest\Assets\DirectoryScannerAssets\Foo;
use Roave\BetterReflectionTest\Assets\DirectoryScannerAssetsFoo\Bar\FooBar as FooBar1;
use Roave\BetterReflectionTest\Assets\DirectoryScannerAssetsFoo\Foo as Foo1;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\Composer\Psr4Locator
 */
class Psr4LocatorTest extends TestCase
{
    public function testWillLocateExistingFileWithMatchingClass() : void
    {
        $astLocator = BetterReflectionSingleton
            ::instance()
            ->astLocator();

        $locator = new Psr4Locator(
            Psr4Mapping::fromArrayMappings([
                'Roave\\BetterReflectionTest\\Assets\\DirectoryScannerAssets\\' => [
                    __DIR__ . '/../../../Assets/DirectoryScannerAssets',
                ],
            ]),
            $astLocator
        );

        $classReflector = new ClassReflector($locator);

        $located = $locator->locateIdentifier(
            $classReflector,
            new Identifier(
                Foo::class,
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        );

        self::assertNotNull($located);
        self::assertSame(Foo::class, $located->getName());
    }

    public function testWillNotLocateNonExistingFileWithMatchingPsr4Class() : void
    {
        $astLocator = BetterReflectionSingleton
            ::instance()
            ->astLocator();

        $locator = new Psr4Locator(
            Psr4Mapping::fromArrayMappings([
                'Roave\\BetterReflectionTest\\Assets\\DirectoryScannerAssets\\' => [
                    __DIR__ . '/../../../Assets/DirectoryScannerAssets',
                ],
            ]),
            $astLocator
        );

        $classReflector = new ClassReflector($locator);

        self::assertNull($locator->locateIdentifier(
            $classReflector,
            new Identifier(
                Foo::class . 'potato',
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        ));
    }

    public function testWillNotLocateExistingFileWithMatchingPsr4ClassAndNoContents() : void
    {
        $astLocator = BetterReflectionSingleton
            ::instance()
            ->astLocator();

        $locator = new Psr4Locator(
            Psr4Mapping::fromArrayMappings([
                'Roave\\BetterReflectionTest\\Assets\\DirectoryScannerAssets\\' => [
                    __DIR__ . '/../../../Assets/DirectoryScannerAssets',
                ],
            ]),
            $astLocator
        );

        $classReflector = new ClassReflector($locator);

        self::assertNull($locator->locateIdentifier(
            $classReflector,
            new Identifier(
                'Roave\\BetterReflectionTest\\Assets\\DirectoryScannerAssets\\Bar\\Empty',
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        ));
    }

    public function testWillLocateAllClassesInMappedPsr4Paths() : void
    {
        $astLocator = BetterReflectionSingleton
            ::instance()
            ->astLocator();

        $locator = new Psr4Locator(
            Psr4Mapping::fromArrayMappings([
                'Roave\\BetterReflectionTest\\Assets\\DirectoryScannerAssets\\' => [
                    __DIR__ . '/../../../Assets/DirectoryScannerAssets',
                ],
                'Roave\\BetterReflectionTest\\Assets\\DirectoryScannerAssetsFoo\\' => [
                    __DIR__ . '/../../../Assets/DirectoryScannerAssetsFoo',
                ],
            ]),
            $astLocator
        );

        self::assertSame(
            [
                FooBar::class,
                Foo::class,
                FooBar1::class,
                Foo1::class,
            ],
            array_map(
                function (Reflection $reflection) : string {
                    return $reflection->getName();
                },
                $locator->locateIdentifiersByType(
                    new ClassReflector($locator),
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                )
            )
        );
    }
}
