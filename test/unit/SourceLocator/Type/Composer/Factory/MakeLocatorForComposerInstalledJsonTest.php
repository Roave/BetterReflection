<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\FailedToParseJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\InvalidProjectDirectory;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingComposerJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\PsrAutoloaderLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function array_column;
use function array_combine;
use function realpath;

#[CoversClass(MakeLocatorForInstalledJson::class)]
class MakeLocatorForComposerInstalledJsonTest extends TestCase
{
    #[DataProvider('expectedLocators')]
    public function testLocatorEquality(string $projectDirectory, SourceLocator $expectedLocatorStructure): void
    {
        self::assertEquals(
            $expectedLocatorStructure,
            (new MakeLocatorForInstalledJson())
                ->__invoke($projectDirectory, BetterReflectionSingleton::instance()->astLocator()),
        );
    }

    /** @return array<string, array{0: string, 1: SourceLocator}> */
    public static function expectedLocators(): array
    {
        $astLocator = BetterReflectionSingleton::instance()->astLocator();

        $projectA                         = realpath(__DIR__ . '/../../../../Assets/ComposerLocators/project-a');
        $projectComposerV2                = realpath(__DIR__ . '/../../../../Assets/ComposerLocators/project-using-composer-v2');
        $projectWithPsrCollisions         = realpath(__DIR__ . '/../../../../Assets/ComposerLocators/project-with-psr-collisions');
        $projectALocator                  = new AggregateSourceLocator([
            new PsrAutoloaderLocator(
                Psr4Mapping::fromArrayMappings([
                    'A\\B\\' => [
                        $projectA . '/vendor/a/b/src/ab_PSR-4_Sources',
                    ],
                    'C\\D\\' => [
                        $projectA . '/vendor/a/b/src/ab_PSR-4_Sources',
                    ],
                    'E\\F\\' => [
                        $projectA . '/vendor/e/f/src/ef_PSR-4_Sources',
                    ],
                ]),
                $astLocator,
            ),
            new PsrAutoloaderLocator(
                Psr0Mapping::fromArrayMappings([
                    'A_B_' => [
                        $projectA . '/vendor/a/b/src/ab_PSR-0_Sources',
                    ],
                    'C_D_' => [
                        $projectA . '/vendor/a/b/src/ab_PSR-0_Sources',
                    ],
                    'E_F_' => [
                        $projectA . '/vendor/e/f/src/ef_PSR-0_Sources',
                    ],
                ]),
                $astLocator,
            ),
            new DirectoriesSourceLocator(
                [
                    $projectA . '/vendor/a/b/src/ab_ClassmapDir',
                    $projectA . '/vendor/e/f/src/ef_ClassmapDir',
                ],
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectA . '/vendor/a/b/src/ab_ClassmapFile',
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectA . '/vendor/e/f/src/ef_ClassmapFile',
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectA . '/vendor/a/b/src/ab_File1.php',
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectA . '/vendor/a/b/src/ab_File2.php',
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectA . '/vendor/e/f/src/ef_File1.php',
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectA . '/vendor/e/f/src/ef_File2.php',
                $astLocator,
            ),
        ]);
        $projectCustomVendorDir           = realpath(__DIR__ . '/../../../../Assets/ComposerLocators/project-with-custom-vendor-dir');
        $projectCustomVendorDirLocator    = new AggregateSourceLocator([
            new PsrAutoloaderLocator(
                Psr4Mapping::fromArrayMappings([
                    'A\\B\\' => [
                        $projectCustomVendorDir . '/custom-vendor/a/b/src/ab_PSR-4_Sources',
                    ],
                    'C\\D\\' => [
                        $projectCustomVendorDir . '/custom-vendor/a/b/src/ab_PSR-4_Sources',
                    ],
                    'E\\F\\' => [
                        $projectCustomVendorDir . '/custom-vendor/e/f/src/ef_PSR-4_Sources',
                    ],
                ]),
                $astLocator,
            ),
            new PsrAutoloaderLocator(
                Psr0Mapping::fromArrayMappings([
                    'A_B_' => [
                        $projectCustomVendorDir . '/custom-vendor/a/b/src/ab_PSR-0_Sources',
                    ],
                    'C_D_' => [
                        $projectCustomVendorDir . '/custom-vendor/a/b/src/ab_PSR-0_Sources',
                    ],
                    'E_F_' => [
                        $projectCustomVendorDir . '/custom-vendor/e/f/src/ef_PSR-0_Sources',
                    ],
                ]),
                $astLocator,
            ),
            new DirectoriesSourceLocator(
                [
                    $projectCustomVendorDir . '/custom-vendor/a/b/src/ab_ClassmapDir',
                    $projectCustomVendorDir . '/custom-vendor/e/f/src/ef_ClassmapDir',
                ],
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectCustomVendorDir . '/custom-vendor/a/b/src/ab_ClassmapFile',
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectCustomVendorDir . '/custom-vendor/e/f/src/ef_ClassmapFile',
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectCustomVendorDir . '/custom-vendor/a/b/src/ab_File1.php',
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectCustomVendorDir . '/custom-vendor/a/b/src/ab_File2.php',
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectCustomVendorDir . '/custom-vendor/e/f/src/ef_File1.php',
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectCustomVendorDir . '/custom-vendor/e/f/src/ef_File2.php',
                $astLocator,
            ),
        ]);
        $projectCustomVendorDirComposerV2 = realpath(__DIR__ . '/../../../../Assets/ComposerLocators/project-with-custom-vendor-dir-using-composer-v2');

        $expectedLocators = [
            [
                $projectA,
                $projectALocator,
            ],
            [
                $projectComposerV2,
                new AggregateSourceLocator([
                    new PsrAutoloaderLocator(
                        Psr4Mapping::fromArrayMappings([
                            'A\\B\\'        => [
                                $projectComposerV2 . '/vendor/a/b/src/ab_PSR-4_Sources',
                            ],
                        ]),
                        $astLocator,
                    ),
                    new PsrAutoloaderLocator(
                        Psr0Mapping::fromArrayMappings([]),
                        $astLocator,
                    ),
                    new DirectoriesSourceLocator([], $astLocator),
                ]),
            ],
            [
                $projectWithPsrCollisions,
                new AggregateSourceLocator([
                    new PsrAutoloaderLocator(
                        Psr4Mapping::fromArrayMappings([
                            'A\\' => [
                                $projectWithPsrCollisions . '/vendor/a/b/src/ab_PSR-4_Sources',
                                $projectWithPsrCollisions . '/vendor/e/f/src/ef_PSR-4_Sources',
                            ],
                            'B\\' => [
                                $projectWithPsrCollisions . '/vendor/a/b/src/ab_PSR-4_Sources',
                            ],
                        ]),
                        $astLocator,
                    ),
                    new PsrAutoloaderLocator(
                        Psr0Mapping::fromArrayMappings([
                            'A_' => [
                                $projectWithPsrCollisions . '/vendor/a/b/src/ab_PSR-0_Sources',
                                $projectWithPsrCollisions . '/vendor/e/f/src/ef_PSR-0_Sources',
                            ],
                            'B_' => [
                                $projectWithPsrCollisions . '/vendor/a/b/src/ab_PSR-0_Sources',
                            ],
                        ]),
                        $astLocator,
                    ),
                    new DirectoriesSourceLocator([], $astLocator),
                ]),
            ],
            [
                // Relative paths are turned into absolute paths too
                __DIR__ . '/../../../../Assets/ComposerLocators/project-a',
                $projectALocator,
            ],
            [
                $projectCustomVendorDir,
                $projectCustomVendorDirLocator,
            ],
            [
                $projectCustomVendorDirComposerV2,
                new AggregateSourceLocator([
                    new PsrAutoloaderLocator(
                        Psr4Mapping::fromArrayMappings([
                            'A\\B\\'        => [
                                $projectCustomVendorDirComposerV2 . '/custom-vendor/a/b/src/ab_PSR-4_Sources',
                            ],
                        ]),
                        $astLocator,
                    ),
                    new PsrAutoloaderLocator(
                        Psr0Mapping::fromArrayMappings([]),
                        $astLocator,
                    ),
                    new DirectoriesSourceLocator([], $astLocator),
                ]),
            ],
        ];

        return array_combine(array_column($expectedLocators, 0), $expectedLocators);
    }

    public function testWillFailToProduceLocatorForProjectWithoutComposerJson(): void
    {
        $this->expectException(MissingComposerJson::class);

        (new MakeLocatorForInstalledJson())
            ->__invoke(
                __DIR__ . '/../../../../Assets/ComposerLocators/project-without-composer.json',
                BetterReflectionSingleton::instance()->astLocator(),
            );
    }

    public function testWillFailToProduceLocatorForProjectWithoutInstalledJson(): void
    {
        $this->expectException(MissingInstalledJson::class);
        $this->expectExceptionMessageMatches('~^Could not locate a "composer/installed.json" file in "[^"]+[\\\\/]Assets[\\\\/]ComposerLocators[\\\\/]project-without-installed.json/vendor"$~');

        (new MakeLocatorForInstalledJson())
            ->__invoke(
                __DIR__ . '/../../../../Assets/ComposerLocators/project-without-installed.json',
                BetterReflectionSingleton::instance()->astLocator(),
            );
    }

    public function testWillFailToProduceLocatorForProjectWithInvalidInstalledJson(): void
    {
        $this->expectException(FailedToParseJson::class);

        (new MakeLocatorForInstalledJson())
            ->__invoke(
                __DIR__ . '/../../../../Assets/ComposerLocators/project-with-invalid-installed-json',
                BetterReflectionSingleton::instance()->astLocator(),
            );
    }

    public function testWillFailToProduceLocatorForInvalidProjectDirectory(): void
    {
        $this->expectException(InvalidProjectDirectory::class);

        (new MakeLocatorForInstalledJson())
            ->__invoke(
                __DIR__ . '/../../../../Assets/ComposerLocators/non-existing',
                BetterReflectionSingleton::instance()->astLocator(),
            );
    }
}
