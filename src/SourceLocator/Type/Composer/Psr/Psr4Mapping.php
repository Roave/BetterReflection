<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Psr;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Exception\InvalidPrefixMapping;

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function is_dir;
use function ltrim;
use function rtrim;
use function str_replace;
use function strlen;
use function strpos;
use function substr;

use const ARRAY_FILTER_USE_KEY;

final class Psr4Mapping implements PsrAutoloaderMapping
{
    /** @var array<string, list<string>> */
    private array $mappings = [];

    private function __construct()
    {
    }

    /** @param array<string, list<string>> $mappings */
    public static function fromArrayMappings(array $mappings): self
    {
        self::assertValidMapping($mappings);

        $instance = new self();

        $instance->mappings = array_map(
            static function (array $directories): array {
                return array_map(static fn (string $directory): string => rtrim($directory, '/'), $directories);
            },
            $mappings,
        );

        return $instance;
    }

    /** {@inheritDoc} */
    public function resolvePossibleFilePaths(Identifier $identifier): array
    {
        if (! $identifier->isClass()) {
            return [];
        }

        $className        = $identifier->getName();
        $matchingPrefixes = $this->matchingPrefixes($className);

        return array_values(array_filter(array_merge(
            [],
            ...array_map(static function (array $paths, string $prefix) use ($className): array {
                $subPath = ltrim(str_replace('\\', '/', substr($className, strlen($prefix))), '/');

                if ($subPath === '') {
                    return [];
                }

                return array_map(static fn (string $path): string => $path . '/' . $subPath . '.php', $paths);
            }, $matchingPrefixes, array_keys($matchingPrefixes)),
        )));
    }

    /** @return array<string, list<string>> */
    private function matchingPrefixes(string $className): array
    {
        return array_filter(
            $this->mappings,
            static fn (string $prefix): bool => strpos($className, $prefix) === 0,
            ARRAY_FILTER_USE_KEY,
        );
    }

    /** {@inheritDoc} */
    public function directories(): array
    {
        return array_values(array_unique(array_merge([], ...array_values($this->mappings))));
    }

    /**
     * @param array<string, list<string>> $mappings
     *
     * @throws InvalidPrefixMapping
     */
    private static function assertValidMapping(array $mappings): void
    {
        foreach ($mappings as $prefix => $paths) {
            if ($prefix === '') {
                throw InvalidPrefixMapping::emptyPrefixGiven();
            }

            if ($paths === []) {
                throw InvalidPrefixMapping::emptyPrefixMappingGiven($prefix);
            }

            foreach ($paths as $path) {
                if (! is_dir($path)) {
                    throw InvalidPrefixMapping::prefixMappingIsNotADirectory($prefix, $path);
                }
            }
        }
    }
}
