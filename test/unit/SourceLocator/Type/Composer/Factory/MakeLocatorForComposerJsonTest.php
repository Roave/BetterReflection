<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Factory;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\FailedToParseJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\InvalidProjectDirectory;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingComposerJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForComposerJson;
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

#[CoversClass(MakeLocatorForComposerJson::class)]
class MakeLocatorForComposerJsonTest extends TestCase
{
    #[DataProvider('expectedLocators')]
    public function testLocatorEquality(string $projectDirectory, SourceLocator $expectedLocatorStructure): void
    {
        self::assertEquals(
            $expectedLocatorStructure,
            (new MakeLocatorForComposerJson())
                ->__invoke($projectDirectory, BetterReflectionSingleton::instance()->astLocator()),
        );
    }

    /** @return array<string, array{0: string, 1: SourceLocator}> */
    public static function expectedLocators(): array
    {
        $astLocator = BetterReflectionSingleton::instance()->astLocator();

        $projectA                 = realpath(__DIR__ . '/../../../../Assets/ComposerLocators/project-a');
        $projectWithPsrCollisions = realpath(__DIR__ . '/../../../../Assets/ComposerLocators/project-with-psr-collisions');
        $projectALocator          = new AggregateSourceLocator([
            new PsrAutoloaderLocator(
                Psr4Mapping::fromArrayMappings([
                    'ProjectA\\'    => [
                        $projectA . '/src/root_PSR-4_Sources',
                    ],
                    'ProjectA\\B\\' => [
                        $projectA . '/src/root_PSR-4_Sources',
                    ],
                ]),
                $astLocator,
            ),
            new PsrAutoloaderLocator(
                Psr0Mapping::fromArrayMappings([
                    'ProjectA_A_' => [
                        $projectA . '/src/root_PSR-0_Sources',
                    ],
                    'ProjectA_B_' => [
                        $projectA . '/src/root_PSR-0_Sources',
                    ],
                ]),
                $astLocator,
            ),
            new DirectoriesSourceLocator(
                [
                    $projectA . '/src/root_ClassmapDir',
                ],
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectA . '/src/root_ClassmapFile',
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectA . '/src/root_File1.php',
                $astLocator,
            ),
            new SingleFileSourceLocator(
                $projectA . '/src/root_File2.php',
                $astLocator,
            ),
        ]);

        $expectedLocators = [
            [
                $projectA,
                $projectALocator,
            ],
            [
                $projectWithPsrCollisions,
                new AggregateSourceLocator([
                    new PsrAutoloaderLocator(
                        Psr4Mapping::fromArrayMappings([
                            'A\\' => [
                                $projectWithPsrCollisions . '/src/root_PSR-4_Sources',
                            ],
                        ]),
                        $astLocator,
                    ),
                    new PsrAutoloaderLocator(
                        Psr0Mapping::fromArrayMappings([
                            'A_' => [
                                $projectWithPsrCollisions . '/src/root_PSR-0_Sources',
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
        ];

        return array_combine(array_column($expectedLocators, 0), $expectedLocators);
    }

    public function testWillFailToProduceLocatorForProjectWithoutComposerJson(): void
    {
        $this->expectException(MissingComposerJson::class);

        (new MakeLocatorForComposerJson())
            ->__invoke(
                __DIR__ . '/../../../../Assets/ComposerLocators/empty-project',
                BetterReflectionSingleton::instance()->astLocator(),
            );
    }

    public function testWillFailToProduceLocatorForProjectWithInvalidComposerJson(): void
    {
        $this->expectException(JsonException::class);

        (new MakeLocatorForComposerJson())
            ->__invoke(
                __DIR__ . '/../../../../Assets/ComposerLocators/project-with-invalid-composer-json',
                BetterReflectionSingleton::instance()->astLocator(),
            );
    }

    public function testWillFailToProduceLocatorForInvalidProjectDirectory(): void
    {
        $this->expectException(InvalidProjectDirectory::class);

        (new MakeLocatorForComposerJson())
            ->__invoke(
                __DIR__ . '/../../../../Assets/ComposerLocators/non-existing',
                BetterReflectionSingleton::instance()->astLocator(),
            );
    }
}
