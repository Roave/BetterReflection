<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\ReflectionTypeDoesNotPointToAClassAlikeType;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;

use function array_key_exists;
use function ltrim;
use function strtolower;

class ReflectionType
{
    private const BUILT_IN_TYPES = [
        'int'      => null,
        'float'    => null,
        'string'   => null,
        'bool'     => null,
        'callable' => null,
        'self'     => null,
        'parent'   => null,
        'array'    => null,
        'iterable' => null,
        'object'   => null,
        'void'     => null,
    ];

    private string $type;

    private bool $allowsNull;

    private Reflector $reflector;

    private function __construct()
    {
    }

    public static function createFromTypeAndReflector(
        string $type,
        bool $allowsNull,
        Reflector $classReflector
    ): self {
        $reflectionType = new self();

        $reflectionType->type       = ltrim($type, '\\');
        $reflectionType->allowsNull = $allowsNull;
        $reflectionType->reflector  = $classReflector;

        return $reflectionType;
    }

    /**
     * Does the parameter allow null?
     */
    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }

    /**
     * Checks if it is a built-in type (i.e., it's not an object...)
     *
     * @see https://php.net/manual/en/reflectiontype.isbuiltin.php
     */
    public function isBuiltin(): bool
    {
        return array_key_exists(strtolower($this->type), self::BUILT_IN_TYPES);
    }

    /**
     * @throws IdentifierNotFound The target type could not be resolved.
     * @throws ReflectionTypeDoesNotPointToAClassAlikeType The type is not pointing to a class-alike symbol.
     * @throws ClassDoesNotExist The target type is not a class.
     */
    public function targetReflectionClass(): ReflectionClass
    {
        if ($this->isBuiltin()) {
            throw ReflectionTypeDoesNotPointToAClassAlikeType::for($this);
        }

        $reflectionClass = $this->reflector->reflect($this->type);

        if (! $reflectionClass instanceof ReflectionClass) {
            throw ClassDoesNotExist::forDifferentReflectionType($reflectionClass);
        }

        return $reflectionClass;
    }

    public function getName(): string
    {
        return $this->type;
    }

    /**
     * Convert this string type to a string
     */
    public function __toString(): string
    {
        return $this->type;
    }
}
