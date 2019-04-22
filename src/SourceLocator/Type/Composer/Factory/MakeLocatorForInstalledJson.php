<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Factory;

use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\FailedToParseJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\InvalidProjectDirectory;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\PsrAutoloaderLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use function array_merge_recursive;
use function file_exists;
use function is_array;
use function is_dir;
use function realpath;

final class MakeLocatorForInstalledJson
{
    public function __invoke(string $installationPath, Locator $astLocator)
    {
        $realInstallationPath = (string) realpath($installationPath);

        if (! is_dir($realInstallationPath)) {
            throw InvalidProjectDirectory::atPath($installationPath);
        }

        $installedJsonPath = $realInstallationPath . '/vendor/composer/installed.json';

        if (! file_exists($installedJsonPath)) {
            throw MissingInstalledJson::inProjectPath($installationPath);
        }

        $installed = json_decode((string) file_get_contents($installedJsonPath), true);

        if (! is_array($installed)) {
            throw FailedToParseJson::inFile($installedJsonPath);
        }

        $classMapPaths       = array_merge(
            [],
            ...array_map(function (array $package) use ($realInstallationPath) : array {
                return $this->prefixWithPackagePath(
                    $this->packageToClassMapPaths($package),
                    $realInstallationPath,
                    $package
                );
            }, $installed)
        );
        $classMapFiles       = array_filter($classMapPaths, 'is_file');
        $classMapDirectories = array_filter($classMapPaths, 'is_dir');
        $filePaths           = array_merge(
            [],
            ...array_map(function (array $package) use ($realInstallationPath) : array {
                return $this->prefixWithPackagePath(
                    $this->packageToFilePaths($package),
                    $realInstallationPath,
                    $package
                );
            }, $installed)
        );

        return new AggregateSourceLocator(array_merge(
            [
                new PsrAutoloaderLocator(
                    Psr4Mapping::fromArrayMappings(array_merge_recursive(
                        [],
                        ...array_map(function (array $package) use ($realInstallationPath) : array {
                            return $this->prefixWithPackagePath(
                                $this->packageToPsr4AutoloadNamespaces($package),
                                $realInstallationPath,
                                $package
                            );
                        }, $installed)
                    )),
                    $astLocator
                ),
                new PsrAutoloaderLocator(
                    Psr0Mapping::fromArrayMappings(array_merge_recursive(
                        [],
                        ...array_map(function (array $package) use ($realInstallationPath) : array {
                            return $this->prefixWithPackagePath(
                                $this->packageToPsr0AutoloadNamespaces($package),
                                $realInstallationPath,
                                $package
                            );
                        }, $installed)
                    )),
                    $astLocator
                ),
                new DirectoriesSourceLocator($classMapDirectories, $astLocator),
            ],
            ...array_map(function (string $file) use ($astLocator) : array {
                return [new SingleFileSourceLocator($file, $astLocator)];
            }, array_merge($classMapFiles, $filePaths))
        ));
    }

    /** @return array<string, array<int, string>> */
    private function packageToPsr4AutoloadNamespaces(array $package) : array
    {
        return array_map(function ($namespacePaths) : array {
            return (array) $namespacePaths;
        }, $package['autoload']['psr-4'] ?? []);
    }

    /** @return array<string, array<int, string>> */
    private function packageToPsr0AutoloadNamespaces(array $package) : array
    {
        return array_map(function ($namespacePaths) : array {
            return (array) $namespacePaths;
        }, $package['autoload']['psr-0'] ?? []);
    }

    /** @return array<string, array<int, string>> */
    private function packageToClassMapPaths(array $package) : array
    {
        return $package['autoload']['classmap'] ?? [];
    }

    /** @return array<string, array<int, string>> */
    private function packageToFilePaths(array $package) : array
    {
        return $package['autoload']['files'] ?? [];
    }

    /**
     * @param array<int|string, string|array<string>> $paths
     * @param array<string, string|array<string>>     $package
     *
     * @return array<int|string, string|array<string>>
     */
    private function prefixWithPackagePath(array $paths, string $trimmedInstallationPath, array $package) : array
    {
        return $this->prefixPaths($paths, $trimmedInstallationPath . '/vendor/' . $package['name'] . '/');
    }

    /**
     * @param array<int|string, string|array<string>> $paths
     *
     * @return array<int|string, string|array<string>>
     */
    private function prefixPaths(array $paths, string $prefix) : array
    {
        return array_map(function ($paths) use ($prefix) {
            if (is_array($paths)) {
                return $this->prefixPaths($paths, $prefix);
            }

            return $prefix . $paths;
        }, $paths);
    }
}
