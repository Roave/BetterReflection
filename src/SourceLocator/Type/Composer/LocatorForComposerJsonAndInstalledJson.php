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
        Assert
            ::that($installationPath)
            ->directory();

        $composerJsonPath  = $installationPath . '/composer.json';
        $installedJsonPath = $installationPath . '/vendor/composer/installed.json';

        Assert
            ::that([$composerJsonPath, $installedJsonPath])
            ->all()
            ->file()
            ->readable();

        $composerJsonContents = file_get_contents($composerJsonPath);
        $installedJsonContents = file_get_contents($composerJsonPath);

        Assert
            ::that([$composerJsonContents, $installedJsonContents])
            ->all()
            ->string();

        $composer  = json_decode($composerJsonContents, true);
        $installed = json_decode($installedJsonContents, true);

        Assert
            ::that([$composer, $installed])
            ->all()
            ->isArray();

        $installedPsr4       = array_merge(
            $this->packageToPsr4AutoloadNamespaces($composer),
            ...array_map([$this, 'packageToPsr4AutoloadNamespaces'], $installed)
        );
        $installedPsr0       = array_merge(
            $this->packageToPsr0AutoloadNamespaces($composer),
            ...array_map([$this, 'packageToPsr0AutoloadNamespaces'], $installed)
        );
        $classMapPaths       = array_merge(
            $this->packageToClassMapPaths($composer),
            ...array_map([$this, 'packageToClassMapPaths'], $installed)
        );
        $classMapFiles       = array_filter('is_file', $classMapPaths);
        $classMapDirectories = array_filter('is_dir', $classMapPaths);
        $filePaths           = array_merge(
            $this->packageToFilePaths($composer),
            ...array_map([$this, 'packageToFilePaths'], $installed)
        );

        return new AggregateSourceLocator(array_merge(
            [
                new PsrAutoloaderLocator(
                    Psr4Mapping::fromArrayMappings(array_merge(
                        $this->packageToPsr4AutoloadNamespaces($composer),
                        ...array_map([$this, 'packageToPsr4AutoloadNamespaces'], $installed)
                    )),
                    $astLocator
                ),
                new PsrAutoloaderLocator(
                    Psr0Mapping::fromArrayMappings(array_merge(
                        $this->packageToPsr0AutoloadNamespaces($composer),
                        ...array_map([$this, 'packageToPsr0AutoloadNamespaces'], $installed)
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
}
