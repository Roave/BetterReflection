<?php

namespace BetterReflection;

use phpDocumentor\Reflection\Types;
use PhpParser\Node\Param as ParamNode;
use PhpParser\Node;
use phpDocumentor\Reflection\Type;

class ReflectionParameter implements \Reflector
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ReflectionFunctionAbstract
     */
    private $function;

    /**
     * @var bool
     */
    private $isOptional;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private $isVariadic;

    /**
     * @var bool
     */
    private $isByReference;

    /**
     * @var Type[]
     */
    private $types;

    /**
     * @var Type
     */
    private $typeHint;

    /**
     * @var int
     */
    private $parameterIndex;

    private function __construct()
    {
    }

    public static function export()
    {
        throw new \Exception('Unable to export statically');
    }

    /**
     * Return string representation of this parameter
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            'Parameter #%d [ %s $%s%s ]',
            $this->parameterIndex,
            $this->isOptional() ? '<optional>' : '<required>',
            $this->getName(),
            $this->isOptional() ? (' = ' . $this->getDefaultValueAsString()) : ''
        );
    }

    /**
     * @param ParamNode $node
     * @param ReflectionFunctionAbstract $function
     * @param int $parameterIndex
     * @return ReflectionParameter
     */
    public static function createFromNode(ParamNode $node, ReflectionFunctionAbstract $function, $parameterIndex)
    {
        $param = new self();
        $param->name = $node->name;
        $param->function = $function;
        $param->isOptional = !is_null($node->default);
        $param->isVariadic = (bool)$node->variadic;
        $param->isByReference = (bool)$node->byRef;
        $param->parameterIndex = (int)$parameterIndex;
        $param->typeHint = TypesFinder::findTypeForAstType($node->type);

        if ($param->isOptional) {
            $param->defaultValue = Reflector::compileNodeExpression($node->default);
        }

        $param->types = TypesFinder::findTypeForParameter($function, $node);

        return $param;
    }

    /**
     * Get the name of the parameter
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the function (or method) that declared this parameter
     *
     * @return ReflectionFunctionAbstract
     */
    public function getDeclaringFunction()
    {
        return $this->function;
    }

    /**
     * Get the class from the method that this parameter belongs to, if it exists.
     *
     * This will return null if the declaring function is not a method.
     *
     * @return ReflectionClass|null
     */
    public function getDeclaringClass()
    {
        if ($this->function instanceof ReflectionMethod) {
            return $this->function->getDeclaringClass();
        }

        return null;
    }

    /**
     * Is the parameter optional?
     *
     * @return bool
     */
    public function isOptional()
    {
        return $this->isOptional;
    }

    /**
     * Get the default value of the parameter
     *
     * @return mixed
     * @throws \LogicException
     */
    public function getDefaultValue()
    {
        if (!$this->isOptional()) {
            throw new \LogicException('This is not an optional parameter, so cannot have a default value');
        }

        return $this->defaultValue;
    }

    public function getDefaultValueAsString()
    {
        $defaultValue = $this->getDefaultValue();
        $type = gettype($defaultValue);
        switch($type) {
            case 'boolean':
                return $defaultValue ? 'true' : 'false';
            case 'integer':
            case 'float':
            case 'double':
                return (string)$defaultValue;
            case 'array':
                return '[]'; // @todo do this less terribly
            case 'NULL':
                return 'null';
            case 'object':
            case 'resource':
            case 'unknown type':
                throw new \RuntimeException(
                    'Default value as an instance of an ' . $type . ' does not make any sense'
                );
        }
    }

    /**
     * Does this method allow null for a parameter?
     *
     * @return bool
     */
    public function allowsNull()
    {
        return $this->isOptional() && $this->getDefaultValue() === null;
    }

    /**
     * @return string[]
     */
    public function getTypeStrings()
    {
        $stringTypes = [];

        foreach ($this->types as $type) {
            $stringTypes[] = (string)$type;
        }
        return $stringTypes;
    }

    /**
     * @return Type[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Find the position of the parameter, left to right, starting at zero
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->parameterIndex;
    }

    /**
     * Get the type hint declared for the parameter
     *
     * @return Type
     */
    public function getTypeHint()
    {
        return $this->typeHint;
    }

    /**
     * Is this parameter an array?
     *
     * @return bool
     */
    public function isArray()
    {
        return ($this->getTypeHint() instanceof Types\Array_);
    }

    /**
     * Is this parameter a callable?
     *
     * @return bool
     */
    public function isCallable()
    {
        return ($this->getTypeHint() instanceof Types\Callable_);
    }

    /**
     * Is this parameter a variadic (denoted by ...$param)
     *
     * @return bool
     */
    public function isVariadic()
    {
        return $this->isVariadic;
    }

    /**
     * Is this parameter passed by reference (denoted by &$param)
     *
     * @return bool
     */
    public function isPassedByReference()
    {
        return $this->isByReference;
    }
}
