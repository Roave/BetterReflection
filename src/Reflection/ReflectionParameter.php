<?php

namespace BetterReflection\Reflection;

use phpDocumentor\Reflection\Types;
use PhpParser\Node\Param as ParamNode;
use PhpParser\Node;
use phpDocumentor\Reflection\Type;
use BetterReflection\NodeCompiler\CompileNodeToValue;
use BetterReflection\TypesFinder\TypesFinder;

class ReflectionParameter implements \Reflector
{
    const CONST_TYPE_NOT_A_CONST = 0;
    const CONST_TYPE_CLASS = 1;
    const CONST_TYPE_DEFINED = 2;

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
     * @var bool
     */
    private $hasDefaultValue;

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
     * @var Type|null
     */
    private $typeHint;

    /**
     * @var int
     */
    private $parameterIndex;

    /**
     * @var bool
     */
    private $isDefaultValueConstant = false;

    /**
     * @var string
     */
    private $defaultValueConstantName = null;

    /**
     * @var int
     */
    private $defaultValueConstantType = self::CONST_TYPE_NOT_A_CONST;

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
            $this->isDefaultValueAvailable()
                ? (' = ' . $this->getDefaultValueAsString())
                : ''
        );
    }

    /**
     * @param ParamNode $node
     * @param ReflectionFunctionAbstract $function
     * @param int $parameterIndex
     * @return ReflectionParameter
     */
    public static function createFromNode(
        ParamNode $node,
        ReflectionFunctionAbstract $function, $parameterIndex
    ) {
        $param = new self();
        $param->name = $node->name;
        $param->function = $function;
        $param->isOptional = (bool)$node->isOptional;
        $param->hasDefaultValue = (null !== $node->default);
        $param->isVariadic = (bool)$node->variadic;
        $param->isByReference = (bool)$node->byRef;
        $param->parameterIndex = (int)$parameterIndex;
        $param->typeHint = TypesFinder::findTypeForAstType($node->type);

        if ($param->hasDefaultValue) {
            $param->parseDefaultValueNode($node->default);
        }

        $param->types = TypesFinder::findTypeForParameter($function, $node);

        return $param;
    }

    private function parseDefaultValueNode(Node $defaultValueNode)
    {
        $this->defaultValue = (new CompileNodeToValue())->__invoke($defaultValueNode);

        if ($defaultValueNode instanceof Node\Expr\ClassConstFetch) {
            $this->isDefaultValueConstant = true;
            $this->defaultValueConstantName = $defaultValueNode->name;
            $this->defaultValueConstantType = self::CONST_TYPE_CLASS;
        }

        if ($defaultValueNode instanceof Node\Expr\ConstFetch
            && !in_array($defaultValueNode->name->parts[0], ['true', 'false', 'null'])) {
            $this->isDefaultValueConstant = true;
            $this->defaultValueConstantName = $defaultValueNode->name->parts[0];
            $this->defaultValueConstantType = self::CONST_TYPE_DEFINED;
        }
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
     * Get the class from the method that this parameter belongs to, if it
     * exists.
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
     * Note this is distinct from "isDefaultValueAvailable" because you can have
     * a default value, but the parameter not be optional. In the example, the
     * $foo parameter isOptional() == false, but isDefaultValueAvailable == true
     *
     * @example someMethod($foo = 'foo', $bar)
     *
     * @return bool
     */
    public function isOptional()
    {
        return $this->isOptional;
    }

    /**
     * Does the parameter have a default, regardless of whether it is optional
     *
     * Note this is distinct from "isOptional" because you can have
     * a default value, but the parameter not be optional. In the example, the
     * $foo parameter isOptional() == false, but isDefaultValueAvailable == true
     *
     * @example someMethod($foo = 'foo', $bar)
     *
     * @return bool
     */
    public function isDefaultValueAvailable()
    {
        return $this->hasDefaultValue;
    }

    /**
     * Get the default value of the parameter
     *
     * @return mixed
     * @throws \LogicException
     */
    public function getDefaultValue()
    {
        if (!$this->isDefaultValueAvailable()) {
            throw new \LogicException('This parameter does not have a default value available');
        }

        return $this->defaultValue;
    }

    /**
     * Get the default value represented as a string
     *
     * @return string
     */
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
            default:
                throw new Exception\InvalidDefaultValueType(sprintf(
                    'Default value as an instance of an %s does not make any sense',
                    $type
                ));
        }
    }

    /**
     * Does this method allow null for a parameter?
     *
     * @return bool
     */
    public function allowsNull()
    {
        if (null == $this->getTypeHint()) {
            return true;
        }

        if (!$this->isDefaultValueAvailable()) {
            return false;
        }

        return $this->getDefaultValue() == null;
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
     * Get the types defined in the docblocks. This returns an array because
     * the parameter may have multiple (compound) types specified (for example
     * when you type hint pipe-separated "string|null", in which case this
     * would return an array of Type objects, one for string, one for null.
     *
     * @see getTypeHint()
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
     * Get the type hint declared for the parameter. This is the real type hint
     * for the parameter, e.g. `method(closure $someFunc)` defined by the
     * method itself, and is separate from the docblock type hints
     *
     * @see getTypes()
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

    /**
     * @return bool
     */
    public function canBePassedByValue()
    {
        return !$this->isPassedByReference();
    }

    /**
     * @return bool
     */
    public function isDefaultValueConstant()
    {
        return $this->isDefaultValueConstant;
    }

    /**
     * @throws \LogicException
     * @return string
     */
    public function getDefaultValueConstantName()
    {
        if (!$this->isDefaultValueConstant()) {
            throw new \LogicException('This parameter is not a constant default value, so cannot have a constant name');
        }

        return $this->defaultValueConstantName;
    }
}
