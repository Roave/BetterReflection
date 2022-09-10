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
    ) {
    }

    /** @internal */
    public static function createFromNode(
        Reflector $reflector,
        ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty $owner,
        Identifier|Name|NullableType|UnionType|IntersectionType $type,
        bool $allowsNull = false,
    ): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType {
        if ($type instanceof NullableType) {
            $type       = $type->type;
            $allowsNull = true;
        }

        if ($type instanceof Identifier || $type instanceof Name) {
            if (
                $type->toLowerString() === 'null'
                || $type->toLowerString() === 'mixed'
                || ! $allowsNull
            ) {
                return new ReflectionNamedType($reflector, $owner, $type);
            }

            return new ReflectionUnionType(
                $reflector,
                $owner,
                new UnionType([$type, new Identifier('null')]),
            );
        }

        if ($type instanceof IntersectionType) {
            return new ReflectionIntersectionType($reflector, $owner, $type);
        }

        if (! $allowsNull) {
            return new ReflectionUnionType($reflector, $owner, $type);
        }

        $hasNull = false;
        foreach ($type->types as $innerUnionType) {
            if (! $innerUnionType instanceof Identifier || $innerUnionType->toLowerString() !== 'null') {
                continue;
            }

            $hasNull = true;
            break;
        }

        if ($hasNull) {
            return new ReflectionUnionType($reflector, $owner, $type);
        }

        $types   = $type->types;
        $types[] = new Identifier('null');

        return new ReflectionUnionType($reflector, $owner, new UnionType($types));
    }

    /** @internal */
    public function getOwner(): ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty
    {
        return $this->owner;
    }

    /**
     * Does the type allow null?
     */
    abstract public function allowsNull(): bool;

    /**
     * Convert this string type to a string
     */
    abstract public function __toString(): string;
}
