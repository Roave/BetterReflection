<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Psr;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Exception\InvalidPrefixMapping;

use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function is_dir;
use function rtrim;
use function str_replace;
use function strpos;

final class Psr0Mapping implements PsrAutoloaderMapping
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

        $className = $identifier->getName();

        foreach ($this->mappings as $prefix => $paths) {
            if (strpos($className, $prefix) === 0) {
                return array_map(
                    static fn (string $path): string => $path . '/' . str_replace(['\\', '_'], '/', $className) . '.php',
                    $paths,
                );
            }
        }

        return [];
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
