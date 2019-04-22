<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Psr;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Exception\InvalidPrefixMapping;

final class Psr0Mapping implements PsrAutoloaderMapping
{
    /** @var array<string, array<int, string>> */
    private $mappings = [];

    private function __construct()
    {
    }

    /** @param array<string, array<int, string>> $mappings */
    public static function fromArrayMappings(array $mappings) : self
    {
        self::assertValidMapping($mappings);

        $instance = new self();

        $instance->mappings = array_map(
            function (array $directories) : array {
                return array_map(function (string $directory) : string {
                    return rtrim($directory, '/');
                }, $directories);
            },
            $mappings
        );

        return $instance;
    }

    public function resolvePossibleFilePaths(Identifier $identifier) : array
    {
        if (! $identifier->isClass()) {
            return [];
        }

        $className = $identifier->getName();

        foreach ($this->mappings as $prefix => $paths) {
            if (0 === strpos($className, $prefix)) {
                return array_map(
                    function (string $path) use ($className) : string {
                        return rtrim($path, '/') . '/' . str_replace(['\\', '_'], '/', $className) . '.php';
                    },
                    $paths
                );
            }
        }

        return [];
    }

    public function directories() : array
    {
        return array_values(array_unique(array_merge([], ...array_values($this->mappings))));
    }

    /**
     * @param array<string, array<int, string>> $mappings
     *
     * @throws InvalidPrefixMapping
     */
    private static function assertValidMapping(array $prefixes) : void
    {
        foreach ($prefixes as $prefix => $paths) {
            if ('' === $prefix) {
                throw InvalidPrefixMapping::emptyPrefixGiven();
            }

            if ([] === $paths) {
                throw InvalidPrefixMapping::emptyPrefixMappingGiven($prefix);
            }

            foreach ($paths as $path) {
                if (! \is_dir($path)) {
                    throw InvalidPrefixMapping::prefixMappingIsNotADirectory($prefix, $path);
                }
            }
        }
    }
}
