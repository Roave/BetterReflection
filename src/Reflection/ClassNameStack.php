<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Roave\BetterReflection\Reflection\Exception\CircularReference;

use function array_key_exists;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class ClassNameStack
{
    /** @param array<class-string, null> $classNames */
    private function __construct(private array $classNames)
    {
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /** @param class-string $className */
    public function push(string $className): self
    {
        if (array_key_exists($className, $this->classNames)) {
            throw CircularReference::fromClassName($className);
        }

        $classNames             = $this->classNames;
        $classNames[$className] = null;

        return new self($classNames);
    }
}
