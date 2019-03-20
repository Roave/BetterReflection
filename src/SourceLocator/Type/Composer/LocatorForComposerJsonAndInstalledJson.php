<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer;

use Assert\Assert;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

// @TODO testme, I'm sad and huge, and horrible, and I can't be left alone with your code
final class LocatorForComposerJsonAndInstalledJson
{
    public function __invoke(string $installationPath, Locator $astLocator)
    {
        $trimmedInstallationPath = rtrim($installationPath, '/');

        Assert
            ::that($trimmedInstallationPath)
            ->directory();

        $composerJsonPath  = $trimmedInstallationPath . '/composer.json';
        $installedJsonPath = $trimmedInstallationPath . '/vendor/composer/installed.json';

        Assert
            ::that([$composerJsonPath, $installedJsonPath])
            ->all()
            ->file()
            ->readable();

        $composerJsonContents  = file_get_contents($composerJsonPath);
        $installedJsonContents = file_get_contents($installedJsonPath);

        Assert
            ::that([$composerJsonContents, $installedJsonContents])
            ->all()
            ->string();

        $composer  = json_decode($composerJsonContents, true, 100, \JSON_THROW_ON_ERROR);
        $installed = json_decode($installedJsonContents, true, 100, \JSON_THROW_ON_ERROR);

        Assert
            ::that([$composer, $installed])
            ->all()
            ->isArray();

        $classMapPaths       = array_merge(
            $this->prefixWithInstallationPath($this->packageToClassMapPaths($composer), $trimmedInstallationPath),
            ...array_map(function (array $package) use ($trimmedInstallationPath) : array {
                return $this->prefixWithPackagePath(
                    $this->packageToClassMapPaths($package),
                    $trimmedInstallationPath,
                    $package
                );
            }, $installed)
        );
        $classMapFiles       = array_filter($classMapPaths, 'is_file');
        $classMapDirectories = array_filter($classMapPaths, 'is_dir');
        $filePaths           = array_merge(
            $this->prefixWithInstallationPath($this->packageToFilePaths($composer), $trimmedInstallationPath),
            ...array_map(function (array $package) use ($trimmedInstallationPath) : array {
                return $this->prefixWithPackagePath(
                    $this->packageToFilePaths($package),
                    $trimmedInstallationPath,
                    $package
                );
            }, $installed)
        );

        return new AggregateSourceLocator(array_merge(
            [
                new PsrAutoloaderLocator(
                    Psr4Mapping::fromArrayMappings(array_merge(
                        $this->prefixWithInstallationPath($this->packageToPsr4AutoloadNamespaces($composer), $trimmedInstallationPath),
                        ...array_map(function (array $package) use ($trimmedInstallationPath) : array {
                            return $this->prefixWithPackagePath(
                                $this->packageToPsr4AutoloadNamespaces($package),
                                $trimmedInstallationPath,
                                $package
                            );
                        }, $installed)
                    )),
                    $astLocator
                ),
                new PsrAutoloaderLocator(
                    Psr0Mapping::fromArrayMappings(array_merge(
                        $this->prefixWithInstallationPath($this->packageToPsr0AutoloadNamespaces($composer), $trimmedInstallationPath),
                        ...array_map(function (array $package) use ($trimmedInstallationPath) : array {
                            return $this->prefixWithPackagePath(
                                $this->packageToPsr0AutoloadNamespaces($package),
                                $trimmedInstallationPath,
                                $package
                            );
                        }, $installed)
                    )),
                    $astLocator
                ),
                new DirectoriesSourceLocator($classMapDirectories, $astLocator),
            ],
            ...array_map(function (string $file) use ($astLocator) : SourceLocator {
                return new SingleFileSourceLocator($file, $astLocator);
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
