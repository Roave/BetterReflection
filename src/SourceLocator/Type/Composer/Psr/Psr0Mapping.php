<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Psr;

// @TODO include assertion lib? Or leave it for later?
use Assert\Assert;
use Roave\BetterReflection\Identifier\Identifier;

final class Psr0Mapping
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

    /** @return string[] */
    public function directories() : array
    {
        return array_values(array_unique(array_merge([], ...array_values($this->mappings))));
    }
}
