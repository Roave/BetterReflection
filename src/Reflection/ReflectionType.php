<?php

namespace BetterReflection\Reflection;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types;

class ReflectionType
{
    /**
     * @var $type
     */
    private $type;

    /**
     * @var bool
     */
    private $allowsNull;

    private function __construct()
    {
    }

    /**
     * @param Type $type
     * @param bool $allowsNull
     * @return ReflectionType
     */
    public static function createFromType(Type $type, $allowsNull)
    {
        $reflectionType = new self();
        $reflectionType->type = $type;
        $reflectionType->allowsNull = (bool)$allowsNull;
        return $reflectionType;
    }

    /**
     * Get a PhpDocumentor type object for this type
     *
     * @return Type
     */
    public function getTypeObject()
    {
        return $this->type;
    }

    /**
     * Does the parameter allow null?
     *
     * @return bool
     */
    public function allowsNull()
    {
        return $this->allowsNull;
    }

    /**
     * Checks if it is a built-in type (i.e., it's not an object...)
     *
     * @see http://php.net/manual/en/reflectiontype.isbuiltin.php
     * @return bool
     */
    public function isBuiltin()
    {
        return (!$this->type instanceof Types\Object_);
    }

    /**
     * Convert this string type to a string
     *
     * @see https://github.com/php/php-src/blob/master/ext/reflection/php_reflection.c#L2993
     * @return string
     */
    public function __toString()
    {
        switch (true) {
            case $this->type instanceof Types\Integer:
                return 'int';
            case $this->type instanceof Types\String_:
                return 'string';
            case $this->type instanceof Types\Array_:
                return 'array';
            case $this->type instanceof Types\Callable_:
                return 'callable';
            case $this->type instanceof Types\Boolean:
                return 'bool';
            case $this->type instanceof Types\Float_:
                return 'float';
            case $this->type instanceof Types\Void_:
                return 'void';
            default:
                return (string)$this->type;
        }
    }
}
