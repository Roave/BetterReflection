<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\TypesFinder\FindParameterType;
use Roave\BetterReflection\TypesFinder\FindTypeFromAst;
use phpDocumentor\Reflection\Types;
use phpDocumentor\Reflection\Types\Self_;
use PhpParser\Node\Param as ParamNode;
use PhpParser\Node;
use phpDocumentor\Reflection\Type;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;

class ReflectionParameter implements \Reflector
{
    const CONST_TYPE_NOT_A_CONST = 0;
    const CONST_TYPE_CLASS = 1;
    const CONST_TYPE_DEFINED = 2;

    /**
     * @var ParamNode
     */
    private $node;

    /**
     * @var ReflectionFunctionAbstract
     */
    private $function;

    /**
     * @var int
     */
    private $parameterIndex;

    /**
     * @var mixed
     */
    private $defaultValue;

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

    /**
     * @var Reflector
     */
    private $reflector;

    private function __construct()
    {
    }

    public static function export()
    {
        throw new \Exception('Unable to export statically');
    }

    /**
     * Create a reflection of a parameter using a class name
     *
     * @param string $className
     * @param string $methodName
     * @param string $parameterName
     * @return ReflectionParameter
     * @throws \OutOfBoundsException
     */
    public static function createFromClassNameAndMethod(
        string $className,
        string $methodName,
        string $parameterName
    ) : self {
        return ReflectionClass::createFromName($className)
            ->getMethod($methodName)
            ->getParameter($parameterName);
    }

    /**
     * Create a reflection of a parameter using an instance
     *
     * @param object $instance
     * @param string $methodName
     * @param string $parameterName
     * @return ReflectionParameter
     * @throws \OutOfBoundsException
     */
    public static function createFromClassInstanceAndMethod(
        $instance,
        string $methodName,
        string $parameterName
    ) : self {
        return ReflectionClass::createFromInstance($instance)
            ->getMethod($methodName)
            ->getParameter($parameterName);
    }

    /**
     * Create a reflection of a parameter using a closure
     *
     * @param \Closure $closure
     * @param string $parameterName
     * @return ReflectionParameter
     */
    public static function createFromClosure(\Closure $closure, string $parameterName) : ReflectionParameter
    {
        return ReflectionFunction::createFromClosure($closure)
            ->getParameter($parameterName);
    }

    /**
     * Create the parameter from the given spec. Possible $spec parameters are:
     *
     *  - [$instance, 'method']
     *  - ['Foo', 'bar']
     *  - ['foo']
     *  - [function () {}]
     *
     * @param string[]|string|\Closure $spec
     * @param string $parameterName
     * @return ReflectionParameter
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public static function createFromSpec($spec, string $parameterName) : self
    {
        if (is_array($spec) && count($spec) === 2) {
            if (is_object($spec[0])) {
                return self::createFromClassInstanceAndMethod($spec[0], $spec[1], $parameterName);
            }

            return self::createFromClassNameAndMethod($spec[0], $spec[1], $parameterName);
        }

        if (is_string($spec)) {
            return ReflectionFunction::createFromName($spec)->getParameter($parameterName);
        }

        if ($spec instanceof \Closure) {
            return self::createFromClosure($spec, $parameterName);
        }

        throw new \InvalidArgumentException('Could not create reflection from the spec given');
    }

    /**
     * Return string representation of this parameter.
     *
     * @return string
     */
    public function __toString() : string
    {
        $isNullableObjectParam = $this->getTypeHint() && $this->getTypeHint() instanceof Types\Object_ && $this->isOptional();

        return sprintf(
            'Parameter #%d [ %s %s%s%s%s$%s%s ]',
            $this->parameterIndex,
            ($this->isVariadic() || $this->isOptional()) ? '<optional>' : '<required>',
            $this->getTypeHint() ? ltrim($this->getTypeHint()->__toString(), '\\') . ' ' : '',
            $isNullableObjectParam ? 'or NULL ' : '',
            $this->isVariadic() ? '...' : '',
            $this->isPassedByReference() ? '&' : '',
            $this->getName(),
            ($this->isOptional() && $this->isDefaultValueAvailable())
                ? (' = ' . var_export($this->getDefaultValue(), true))
                : ''
        );
    }

    /**
     * @param Reflector $reflector
     * @param ParamNode $node
     * @param ReflectionFunctionAbstract $function
     * @param int $parameterIndex
     * @return ReflectionParameter
     */
    public static function createFromNode(
        Reflector $reflector,
        ParamNode $node,
        ReflectionFunctionAbstract $function,
        int $parameterIndex
    ) : self {
        $param = new self();
        $param->reflector = $reflector;
        $param->node = $node;
        $param->function = $function;
        $param->parameterIndex = (int)$parameterIndex;
        return $param;
    }

    private function parseDefaultValueNode() : void
    {
        if (!$this->isDefaultValueAvailable()) {
            throw new \LogicException('This parameter does not have a default value available');
        }

        $defaultValueNode = $this->node->default;

        if ($defaultValueNode instanceof Node\Expr\ClassConstFetch) {
            $className = $this->getRealClassName(implode('\\', $defaultValueNode->class->parts));

            if ($className instanceof Self_) {
                $className = $this->findParentClassDeclaringConstant($defaultValueNode->name);
            }

            $this->isDefaultValueConstant = true;
            $this->defaultValueConstantName = ltrim((string) $className, '\\') . '::' . $defaultValueNode->name;
            $this->defaultValueConstantType = self::CONST_TYPE_CLASS;
        }

        if ($defaultValueNode instanceof Node\Expr\ConstFetch
            && !in_array(strtolower($defaultValueNode->name->parts[0]), ['true', 'false', 'null'])) {
            $this->isDefaultValueConstant = true;
            $this->defaultValueConstantName = $defaultValueNode->name->parts[0];
            $this->defaultValueConstantType = self::CONST_TYPE_DEFINED;
            $this->defaultValue = null;
            return;
        }

        $this->defaultValue = (new CompileNodeToValue())->__invoke(
            $defaultValueNode,
            new CompilerContext($this->reflector, $this->getDeclaringClass())
        );
    }

    /**
     * @throws \LogicException
     */
    private function findParentClassDeclaringConstant(string $constantName): string
    {
        $class = $this->function->getDeclaringClass();
        do {
            if ($class->hasConstant($constantName)) {
                return $class->getName();
            }
        } while ($class = $class->getParentClass());

        throw new LogicException("Failed to find parent class of constant '$constantName'.");
    }

    /**
     * Get the name of the parameter.
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->node->name;
    }

    /**
     * Get the function (or method) that declared this parameter.
     *
     * @return ReflectionFunctionAbstract
     */
    public function getDeclaringFunction() : ReflectionFunctionAbstract
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
    public function isOptional() : bool
    {
        return ((bool)$this->node->isOptional) || $this->isVariadic();
    }

    /**
     * Does the parameter have a default, regardless of whether it is optional.
     *
     * Note this is distinct from "isOptional" because you can have
     * a default value, but the parameter not be optional. In the example, the
     * $foo parameter isOptional() == false, but isDefaultValueAvailable == true
     *
     * @example someMethod($foo = 'foo', $bar)
     *
     * @return bool
     */
    public function isDefaultValueAvailable() : bool
    {
        return (null !== $this->node->default);
    }

    /**
     * Get the default value of the parameter.
     *
     * @return mixed
     * @throws \LogicException
     */
    public function getDefaultValue()
    {
        $this->parseDefaultValueNode();

        return $this->defaultValue;
    }

    /**
     * Get the default value represented as a string.
     *
     * @return string
     * @deprecated Use `var_export($reflection->getDefaultValue(), true)` instead
     */
    public function getDefaultValueAsString() : string
    {
        return var_export($this->getDefaultValue(), true);
    }

    /**
     * Does this method allow null for a parameter?
     *
     * @return bool
     */
    public function allowsNull() : bool
    {
        if (null === $this->getTypeHint()) {
            return true;
        }

        if (!$this->isDefaultValueAvailable()) {
            return false;
        }

        return $this->getDefaultValue() === null;
    }

    /**
     * Get the DocBlock type hints as an array of strings.
     *
     * @return string[]
     */
    public function getDocBlockTypeStrings() : array
    {
        $stringTypes = [];

        foreach ($this->getDocBlockTypes() as $type) {
            $stringTypes[] = (string)$type;
        }
        return $stringTypes;
    }

    /**
     * Get the types defined in the DocBlocks. This returns an array because
     * the parameter may have multiple (compound) types specified (for example
     * when you type hint pipe-separated "string|null", in which case this
     * would return an array of Type objects, one for string, one for null.
     *
     * @see getTypeHint()
     * @return Type[]
     */
    public function getDocBlockTypes() : array
    {
        return  (new FindParameterType())->__invoke($this->function, $this->node);
    }

    /**
     * Find the position of the parameter, left to right, starting at zero.
     *
     * @return int
     */
    public function getPosition() : int
    {
        return $this->parameterIndex;
    }

    /**
     * Get the type hint declared for the parameter. This is the real type hint
     * for the parameter, e.g. `method(closure $someFunc)` defined by the
     * method itself, and is separate from the DocBlock type hints.
     *
     * @see getDocBlockTypes()
     * @return Type|null
     */
    public function getTypeHint() : ?Type
    {
        return $this->getRealClassName($this->node->type);
    }

    /**
     * @param string|null|Node
     */
    private function getRealClassName($className) : ?Type
    {
        $namespaceForType = $this->function instanceof ReflectionMethod
            ? $this->function->getDeclaringClass()->getNamespaceName()
            : $this->function->getNamespaceName();

        return (new FindTypeFromAst())->__invoke(
            $className,
            $this->function->getLocatedSource(),
            $namespaceForType
        );
    }

    /**
     * Get the ReflectionType instance representing the type declaration for
     * this parameter
     *
     * (note: this has nothing to do with DocBlocks).
     *
     * @return ReflectionType|null
     */
    public function getType() : ?ReflectionType
    {
        if (null === $this->getTypeHint()) {
            return null;
        }

        return ReflectionType::createFromType($this->getTypeHint(), $this->allowsNull());
    }

    /**
     * Does this parameter have a type declaration?
     *
     * (note: this has nothing to do with DocBlocks).
     *
     * @return bool
     */
    public function hasType() : bool
    {
        return null !== $this->getTypeHint();
    }

    /**
     * Set the parameter type declaration.
     *
     * You must use the phpDocumentor reflection type classes as the parameter.
     *
     * @param Type $newParameterType
     */
    public function setType(Type $newParameterType) : void
    {
        $this->node->type = new Node\Name((string)$newParameterType);
    }

    /**
     * Remove the parameter type declaration completely.
     */
    public function removeType() : void
    {
        $this->node->type = null;
    }

    /**
     * Is this parameter an array?
     *
     * @return bool
     */
    public function isArray() : bool
    {
        return ($this->getTypeHint() instanceof Types\Array_);
    }

    /**
     * Is this parameter a callable?
     *
     * @return bool
     */
    public function isCallable() : bool
    {
        return ($this->getTypeHint() instanceof Types\Callable_);
    }

    /**
     * Is this parameter a variadic (denoted by ...$param).
     *
     * @return bool
     */
    public function isVariadic() : bool
    {
        return (bool)$this->node->variadic;
    }

    /**
     * Is this parameter passed by reference (denoted by &$param).
     *
     * @return bool
     */
    public function isPassedByReference() : bool
    {
        return (bool)$this->node->byRef;
    }

    /**
     * @return bool
     */
    public function canBePassedByValue() : bool
    {
        return !$this->isPassedByReference();
    }

    /**
     * @return bool
     */
    public function isDefaultValueConstant() : bool
    {
        $this->parseDefaultValueNode();
        return $this->isDefaultValueConstant;
    }

    /**
     * @throws \LogicException
     * @return string
     */
    public function getDefaultValueConstantName() : string
    {
        $this->parseDefaultValueNode();
        if (!$this->isDefaultValueConstant()) {
            throw new \LogicException('This parameter is not a constant default value, so cannot have a constant name');
        }

        return $this->defaultValueConstantName;
    }

    /**
     * Gets a ReflectionClass for the type hint (returns null if not a class)
     *
     * @return ReflectionClass|null
     * @throws \RuntimeException
     */
    public function getClass() : ?ReflectionClass
    {
        $hint = $this->getTypeHint();

        if ($hint instanceof Types\Self_) {
            return $this->getDeclaringClass();
        }

        if ($hint instanceof Types\Parent_) {
            return $this->getDeclaringClass()->getParentClass();
        }

        if (! $hint instanceof Types\Object_) {
            return null;
        }

        $fqsen = $hint->getFqsen();

        if (! $fqsen) {
            return null;
        }

        if (!$this->reflector instanceof ClassReflector) {
            throw new \RuntimeException('Unable to reflect class type because we were not given a ClassReflector');
        }

        return $this->reflector->reflect($fqsen->__toString());
    }

    /**
     * {@inheritdoc}
     * @throws Exception\Uncloneable
     */
    public function __clone()
    {
        throw Exception\Uncloneable::fromClass(__CLASS__);
    }
}
