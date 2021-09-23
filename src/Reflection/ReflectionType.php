<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;

abstract class ReflectionType
{
    protected function __construct(private bool $allowsNull)
    {
    }

    public static function createFromTypeAndReflector(Identifier|Name|ComplexType $type, bool $forceAllowsNull = false): ReflectionNamedType|ReflectionUnionType
    {
        $allowsNull = $forceAllowsNull;
        if ($type instanceof NullableType) {
            $type       = $type->type;
            $allowsNull = true;
        }

        if ($type instanceof Identifier || $type instanceof Name) {
            return new ReflectionNamedType($type, $allowsNull);
        }

        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @phpstan-ignore-next-line
         */
        return new ReflectionUnionType($type, $allowsNull);
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
