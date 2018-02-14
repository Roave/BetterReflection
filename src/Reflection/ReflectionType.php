<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

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

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $allowsNull;

    private function __construct()
    {
    }

    public static function createFromType(string $type, bool $allowsNull) : self
    {
        $reflectionType             = new self();
        $reflectionType->type       = ltrim($type, '\\');
        $reflectionType->allowsNull = $allowsNull;
        return $reflectionType;
    }

    /**
     * Does the parameter allow null?
     */
    public function allowsNull() : bool
    {
        return $this->allowsNull;
    }

    /**
     * Checks if it is a built-in type (i.e., it's not an object...)
     *
     * @see https://php.net/manual/en/reflectiontype.isbuiltin.php
     */
    public function isBuiltin() : bool
    {
        return array_key_exists(strtolower($this->type), self::BUILT_IN_TYPES);
    }

    /**
     * Convert this string type to a string
     *
     * @see https://github.com/php/php-src/blob/master/ext/reflection/php_reflection.c#L2993
     */
    public function __toString() : string
    {
        return $this->type;
    }
}
