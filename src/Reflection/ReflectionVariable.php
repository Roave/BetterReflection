<?php

namespace BetterReflection\Reflection;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types;

class ReflectionVariable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $declaredAt;

    /**
     * @var $type
     */
    private $type;

    /**
     * @param string $name
     * @param int $declaredAt
     * @param string $type
     * @return ReflectionType
     */
    public static function createFromName($name, ReflectionType $type = null, $declaredAt)
    {
        $reflectionType = new self();
        $reflectionType->name = $name;
        $reflectionType->declaredAt = $declaredAt;
        $reflectionType->type = $type;
        return $reflectionType;
    }

    public function getName() 
    {
        return $this->name;
    }

    public function getDeclaredAt() 
    {
        return $this->declaredAt;
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
        return sprintf('@var $%s (%s): %s', $this->name, $this->type, $this->declaredAt);
    }
}
