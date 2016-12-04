<?php

namespace BetterReflection\Reflection;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types;
use PhpParser\NodeAbstract;
use PhpParser\Node\Param;
use PhpParser\Node\Expr\Variable;
use BetterReflection\Reflection\ReflectionType;
use BetterReflection\Reflection\ReflectionVariable;

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
     * @var ReflectionType $type
     */
    private $type;

    public static function createFromParamAndType(Param $param, ReflectionType $type): ReflectionVariable
    {
        return self::createFromNodeAndType($param, $type);
    }

    public static function createFromVariableAndType(Variable $variable, ReflectionType $type): ReflectionVariable
    {
        return self::createFromNodeAndType($variable, $type);
    }

    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Return the reflection type for this variable.
     */
    public function getType(): ReflectionType
    {
        return $this->type;
    }

    /**
     * Returns the offset into the code string of the first character that is part of the node.
     */
    public function getStartPos(): int
    {
        return $this->startPos;
    }

    /**
     * Returns the offset into the code string of the last character that is part of the node.
     */
    public function getEndPos(): int
    {
        return $this->endPos;
    }

    /**
     * Create a new reflection variable, 
     *
     * NOTE: This method is private as both `Variables` and
     *       `Params` have the same properties but do not extend a common type.
     *
     * NOTE: The startFilePos and endFilePos attributes should be available on
     *       the node, which means that the Lexer should be configured to provide
     *       them.
     */
    private static function createFromNodeAndType(NodeAbstract $node, ReflectionType $type): ReflectionVariable
    {
        $reflectionVariable = new self();
        $reflectionVariable->name = $node->name;
        $reflectionVariable->type = $type;
        $reflectionVariable->startPos = $node->getAttribute('startFilePos');
        $reflectionVariable->endPos = $node->getAttribute('endFilePos');

        return $reflectionVariable;
    }

}
