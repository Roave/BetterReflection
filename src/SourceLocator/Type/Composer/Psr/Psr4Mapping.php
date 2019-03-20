<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Psr;

// @TODO include assertion lib? Or leave it for later?
use Assert\Assert;
use Roave\BetterReflection\Identifier\Identifier;

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
        Assert
            ::thatAll($mappings)
            ->isArray()
            ->notEmpty();

        Assert
            ::thatAll(array_keys($mappings))
            ->string()
            ->notEmpty();

        Assert
            ::thatAll(array_merge([], ...array_values($mappings)))
            ->string()
            ->notEmpty()
            ->directory();

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
}
