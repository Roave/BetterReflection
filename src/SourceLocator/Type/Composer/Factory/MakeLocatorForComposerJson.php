<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Factory;

use Assert\Assert;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\PsrAutoloaderLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

// @TODO testme, I'm sad and huge, and horrible, and I can't be left alone with your code
final class MakeLocatorForComposerJson
{
    public function __invoke(string $installationPath, Locator $astLocator)
    {
        $trimmedInstallationPath = rtrim($installationPath, '/');

        Assert
            ::that($trimmedInstallationPath)
            ->directory();

        $composerJsonPath = $trimmedInstallationPath . '/composer.json';

        Assert
            ::that($composerJsonPath)
            ->file()
            ->readable();

        $composerJsonContents = file_get_contents($composerJsonPath);

        Assert
            ::that($composerJsonContents)
            ->string();

        $composer = json_decode($composerJsonContents, true, 100, \JSON_THROW_ON_ERROR);

        Assert
            ::that($composer)
            ->isArray();

        $classMapPaths       = $this->prefixWithInstallationPath($this->packageToClassMapPaths($composer), $trimmedInstallationPath);
        $classMapFiles       = array_filter($classMapPaths, 'is_file');
        $classMapDirectories = array_filter($classMapPaths, 'is_dir');
        $filePaths           = $this->prefixWithInstallationPath($this->packageToFilePaths($composer), $trimmedInstallationPath);

        return new AggregateSourceLocator(array_merge(
            [
                new PsrAutoloaderLocator(
                    Psr4Mapping::fromArrayMappings(
                        $this->prefixWithInstallationPath($this->packageToPsr4AutoloadNamespaces($composer), $trimmedInstallationPath)
                    ),
                    $astLocator
                ),
                new PsrAutoloaderLocator(
                    Psr0Mapping::fromArrayMappings(
                        $this->prefixWithInstallationPath($this->packageToPsr0AutoloadNamespaces($composer), $trimmedInstallationPath)
                    ),
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
     *
     * @return array<int|string, string|array<string>>
     */
    private function prefixWithInstallationPath(array $paths, string $trimmedInstallationPath) : array
    {
        return $this->prefixPaths($paths, $trimmedInstallationPath . '/');
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
