<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use Roave\BetterReflection\Reflector\Reflector;

abstract class ReflectionType
{
    protected function __construct(
        protected Reflector $reflector,
        protected ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty $owner,
        private bool $allowsNull,
    ) {
    }

    /**
     * @internal
     */
    public static function createFromNode(
        Reflector $reflector,
        ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty $owner,
        Identifier|Name|NullableType|UnionType|IntersectionType $type,
        bool $forceAllowsNull = false,
    ): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType {
        $allowsNull = $forceAllowsNull;
        if ($type instanceof NullableType) {
            $type       = $type->type;
            $allowsNull = true;
        }

        if ($type instanceof Identifier || $type instanceof Name) {
            return new ReflectionNamedType($reflector, $owner, $type, $allowsNull);
        }

        if ($type instanceof IntersectionType) {
            return new ReflectionIntersectionType($reflector, $owner, $type, $allowsNull);
        }

        return new ReflectionUnionType($reflector, $owner, $type, $allowsNull);
    }

    /**
     * Does the parameter allow null?
     */
    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }

    /**
     * Convert this string type to a string
     */
    abstract public function __toString(): string;
}
