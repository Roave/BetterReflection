<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Psr;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Exception\InvalidPrefixMapping;

final class Psr4Mapping implements PsrAutoloaderMapping
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

    /** @return string[] */
    public function resolvePossibleFilePaths(Identifier $identifier) : array
    {
        if (! $identifier->isClass()) {
            return [];
        }

        $className        = $identifier->getName();
        $matchingPrefixes = $this->matchingPrefixes($className);

        return array_values(array_filter(array_merge(
            [],
            ...array_map(function (array $paths, string $prefix) use ($className) : array {
                $subPath = ltrim(str_replace('\\', '/', substr($className, strlen($prefix))), '/');

                if ('' === $subPath) {
                    return [];
                }

                return array_map(function (string $path) use ($subPath) : string {
                    return rtrim($path, '/') . '/' . $subPath . '.php';
                }, $paths);
            }, $matchingPrefixes, array_keys($matchingPrefixes))
        )));
    }

    private function matchingPrefixes(string $className) : array
    {
        return array_filter(
            $this->mappings,
            static function (string $prefix) use ($className) : bool {
                return 0 === strpos($className, $prefix);
            },
            \ARRAY_FILTER_USE_KEY
        );
    }

    /** @return string[] */
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
