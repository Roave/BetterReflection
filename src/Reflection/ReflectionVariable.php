<?php

namespace BetterReflection\Reflection;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types;
use PhpParser\NodeAbstract;
use PhpParser\Node\Param;
use PhpParser\Node\Expr\Variable;

class ReflectionVariable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $startPos;

    /**
     * @var int
     */
    private $endPos;

    /**
     * @var $type
     */
    private $type;

    public static function createFromParamAndType(Param $param, ReflectionType $type)
    {
        return self::createFromNodeAndType($param, $type);
    }

    public static function createFromVariableAndType(Variable $variable, ReflectionType $type)
    {
        return self::createFromNodeAndType($variable, $type);
    }

    private static function createFromNodeAndType(NodeAbstract $node, ReflectionType $type)
    {
        $reflectionVariable = new self();
        $reflectionVariable->name = $node->name;
        $reflectionVariable->type = $type;
        $reflectionVariable->startPos = $node->getAttribute('startPos');
        $reflectionVariable->endPos = $node->getAttribute('endPos');

        return $reflectionVariable;
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
