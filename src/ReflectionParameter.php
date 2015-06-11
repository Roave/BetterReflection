<?php

namespace Asgrim;

use PhpParser\Node\Param as ParamNode;
use PhpParser\Node;

class ReflectionParameter
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

    private function __construct()
    {
    }

    /**
     * @param ParamNode $node
     * @param ReflectionFunctionAbstract $function
     * @return ReflectionParameter
     */
    public static function createFromNode(ParamNode $node, ReflectionFunctionAbstract $function)
    {
        $param = new self();
        $param->name = $node->name;
        $param->function = $function;
        $param->isOptional = !is_null($node->default);
        $param->isVariadic = $node->variadic;
        $param->isByReference = $node->byRef;

        if ($param->isOptional) {
            switch (get_class($node->default)) {
                case Node\Scalar\String_::class:
                case Node\Scalar\DNumber::class:
                case Node\Scalar\LNumber::class:
                    $param->defaultValue = $node->default->value;
                    break;
                case Node\Expr\Array_::class:
                    $param->defaultValue = []; // @todo compile expression
                    break;
                case Node\Expr\ConstFetch::class:
                    if ($node->default->name->parts[0] == 'null') {
                        $param->defaultValue = null;
                    } else {
                        throw new \LogicException('Other ConstFetch types are not implemented yet');
                    }
                    break;
                default:
                    throw new \LogicException('Unable to determine default value for parameter');
            }
        }

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

    /**
     * Does this method allow null for a parameter?
     *
     * @return bool
     */
    public function allowsNull()
    {
        return $this->isOptional() && $this->getDefaultValue() === null;
    }
}