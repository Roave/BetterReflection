<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Factory;

use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\FailedToParseJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\InvalidProjectDirectory;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingComposerJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\PsrAutoloaderLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function file_get_contents;
use function is_array;
use function is_dir;
use function is_file;
use function json_decode;
use function realpath;

/**
 * @psalm-type ComposerAutoload array{
 *  psr-0?: array<string, string|list<string>>,
 *  psr-4?: array<string, string|list<string>>,
 *  classmap?: list<string>,
 *  files?: list<string>,
 *  exclude-from-classmap?: list<string>
 * }
 */
final class MakeLocatorForComposerJson
{
    public function __invoke(string $installationPath, Locator $astLocator): SourceLocator
    {
        $realInstallationPath = (string) realpath($installationPath);

        if (! is_dir($realInstallationPath)) {
            throw InvalidProjectDirectory::atPath($installationPath);
        }

        $composerJsonPath = $realInstallationPath . '/composer.json';

        if (! is_file($composerJsonPath)) {
            throw MissingComposerJson::inProjectPath($installationPath);
        }

        /** @psalm-var array{autoload: ComposerAutoload}|null $composer */
        $composer = json_decode((string) file_get_contents($composerJsonPath), true);

        if (! is_array($composer)) {
            throw FailedToParseJson::inFile($composerJsonPath);
        }

        $pathPrefix          = $realInstallationPath . '/';
        $classMapPaths       = $this->prefixPaths($this->packageToClassMapPaths($composer), $pathPrefix);
        $classMapFiles       = array_filter($classMapPaths, 'is_file');
        $classMapDirectories = array_values(array_filter($classMapPaths, 'is_dir'));
        $filePaths           = $this->prefixPaths($this->packageToFilePaths($composer), $pathPrefix);

        return new AggregateSourceLocator(array_merge(
            [
                new PsrAutoloaderLocator(
                    Psr4Mapping::fromArrayMappings(
                        $this->prefixWithInstallationPath($this->packageToPsr4AutoloadNamespaces($composer), $pathPrefix),
                    ),
                    $astLocator,
                ),
                new PsrAutoloaderLocator(
                    Psr0Mapping::fromArrayMappings(
                        $this->prefixWithInstallationPath($this->packageToPsr0AutoloadNamespaces($composer), $pathPrefix),
                    ),
                    $astLocator,
                ),
                new DirectoriesSourceLocator($classMapDirectories, $astLocator),
            ],
            ...array_map(static fn (string $file): array => [new SingleFileSourceLocator($file, $astLocator)], array_merge($classMapFiles, $filePaths)),
        ));
    }

    /**
     * @param array{autoload: ComposerAutoload} $package
     *
     * @return array<string, list<string>>
     */
    private function packageToPsr4AutoloadNamespaces(array $package): array
    {
        return array_map(static fn (string|array $namespacePaths): array => (array) $namespacePaths, $package['autoload']['psr-4'] ?? []);
    }

    /**
     * @param array{autoload: ComposerAutoload} $package
     *
     * @return array<string, list<string>>
     */
    private function packageToPsr0AutoloadNamespaces(array $package): array
    {
        return array_map(static fn (string|array $namespacePaths): array => (array) $namespacePaths, $package['autoload']['psr-0'] ?? []);
    }

    /**
     * @param array{autoload: ComposerAutoload} $package
     *
     * @return list<string>
     */
    private function packageToClassMapPaths(array $package): array
    {
        return $package['autoload']['classmap'] ?? [];
    }

    /**
     * @param array{autoload: ComposerAutoload} $package
     *
     * @return list<string>
     */
    private function packageToFilePaths(array $package): array
    {
        return $package['autoload']['files'] ?? [];
    }

    /**
     * @param array<string, list<string>> $paths
     *
     * @return array<string, list<string>>
     */
    private function prefixWithInstallationPath(array $paths, string $trimmedInstallationPath): array
    {
        return array_map(fn (array $paths): array => $this->prefixPaths($paths, $trimmedInstallationPath), $paths);
    }

    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    private function prefixPaths(array $paths, string $prefix): array
    {
        return array_map(static fn (string $path): string => $prefix . $path, $paths);
    }
}
