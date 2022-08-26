<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Factory;

use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\FailedToParseJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\InvalidProjectDirectory;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingComposerJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\PsrAutoloaderLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

use function array_filter;
use function array_map;
use function array_merge;
use function array_merge_recursive;
use function array_values;
use function file_get_contents;
use function is_array;
use function is_dir;
use function is_file;
use function json_decode;
use function realpath;
use function rtrim;

/** @psalm-import-type ComposerAutoload from MakeLocatorForComposerJson */
final class MakeLocatorForInstalledJson
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

        /** @psalm-var array{autoload: ComposerAutoload, config: array{vendor-dir?: string}}|null $composer */
        $composer  = json_decode((string) file_get_contents($composerJsonPath), true);
        $vendorDir = $composer['config']['vendor-dir'] ?? 'vendor';
        $vendorDir = rtrim($vendorDir, '/');

        $installedJsonPath = $realInstallationPath . '/' . $vendorDir . '/composer/installed.json';

        if (! is_file($installedJsonPath)) {
            throw MissingInstalledJson::inProjectPath($realInstallationPath . '/' . $vendorDir);
        }

        /** @var array{packages: list<mixed[]>}|list<mixed[]>|null $installedJson */
        $installedJson = json_decode((string) file_get_contents($installedJsonPath), true);

        if (! is_array($installedJson)) {
            throw FailedToParseJson::inFile($installedJsonPath);
        }

        /** @psalm-var list<array{name: string, autoload: array{classmap: list<string>, files: list<string>, psr-4: array<string, list<string>|string>, psr-0: array<string, list<string>|string>}}> $installed */
        $installed = $installedJson['packages'] ?? $installedJson;

        $classMapPaths       = array_merge(
            [],
            ...array_map(fn (array $package): array => $this->prefixPaths(
                $this->packageToClassMapPaths($package),
                $this->packagePrefixPath($realInstallationPath, $package, $vendorDir),
            ), $installed),
        );
        $classMapFiles       = array_filter($classMapPaths, 'is_file');
        $classMapDirectories = array_values(array_filter($classMapPaths, 'is_dir'));
        $filePaths           = array_merge(
            [],
            ...array_map(fn (array $package): array => $this->prefixPaths(
                $this->packageToFilePaths($package),
                $this->packagePrefixPath($realInstallationPath, $package, $vendorDir),
            ), $installed),
        );

        return new AggregateSourceLocator(array_merge(
            [
                new PsrAutoloaderLocator(
                    Psr4Mapping::fromArrayMappings(array_merge_recursive(
                        [],
                        ...array_map(fn (array $package): array => $this->prefixWithPackagePath(
                            $this->packageToPsr4AutoloadNamespaces($package),
                            $realInstallationPath,
                            $package,
                            $vendorDir,
                        ), $installed),
                    )),
                    $astLocator,
                ),
                new PsrAutoloaderLocator(
                    Psr0Mapping::fromArrayMappings(array_merge_recursive(
                        [],
                        ...array_map(fn (array $package): array => $this->prefixWithPackagePath(
                            $this->packageToPsr0AutoloadNamespaces($package),
                            $realInstallationPath,
                            $package,
                            $vendorDir,
                        ), $installed),
                    )),
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

    /** @param array{name: string, autoload: ComposerAutoload} $package */
    private function packagePrefixPath(string $trimmedInstallationPath, array $package, string $vendorDir): string
    {
        return $trimmedInstallationPath . '/' . $vendorDir . '/' . $package['name'] . '/';
    }

    /**
     * @param array<int|string, array<string>>                $paths
     * @param array{name: string, autoload: ComposerAutoload} $package
     *
     * @return array<int|string, string|array<string>>
     */
    private function prefixWithPackagePath(array $paths, string $trimmedInstallationPath, array $package, string $vendorDir): array
    {
        $prefix = $this->packagePrefixPath($trimmedInstallationPath, $package, $vendorDir);

        return array_map(fn (array $paths): array => $this->prefixPaths($paths, $prefix), $paths);
    }

    /**
     * @param array<int|string, string> $paths
     *
     * @return array<int|string, string>
     */
    private function prefixPaths(array $paths, string $prefix): array
    {
        return array_map(static fn (string $path): string => $prefix . $path, $paths);
    }
}
