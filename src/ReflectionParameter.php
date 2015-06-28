<?php

namespace Asgrim;

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
        $param->isVariadic = $node->variadic;
        $param->isByReference = $node->byRef;
        $param->parameterIndex = $parameterIndex;

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
    public function getTypes()
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
    public function getTypeObjects()
    {
        return $this->types;
    }
}
