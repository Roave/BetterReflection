<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Closure;
use Exception;
use InvalidArgumentException;
use LogicException;
use phpDocumentor\Reflection\Type;
use PhpParser\Node;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param as ParamNode;
use PhpParser\Node\Stmt\Namespace_;
use Reflector as CoreReflector;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\StringCast\ReflectionParameterStringCast;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\TypesFinder\FindParameterType;
use Roave\BetterReflection\Util\CalculateReflectionColum;
use RuntimeException;

class ReflectionParameter implements CoreReflector
{
    private const CONST_TYPE_NOT_A_CONST = 0;
    private const CONST_TYPE_CLASS       = 1;
    private const CONST_TYPE_DEFINED     = 2;

    /**
     * @var ParamNode
     */
    private $node;

    /**
     * @var Namespace_|null
     */
    private $declaringNamespace;

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
     * @var string|null
     */
    private $defaultValueConstantName;

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

    public static function export() : void
    {
        throw new Exception('Unable to export statically');
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
    public static function createFromClosure(Closure $closure, string $parameterName) : ReflectionParameter
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
        if (\is_array($spec) && \count($spec) === 2) {
            if (\is_object($spec[0])) {
                return self::createFromClassInstanceAndMethod($spec[0], $spec[1], $parameterName);
            }

            return self::createFromClassNameAndMethod($spec[0], $spec[1], $parameterName);
        }

        if (\is_string($spec)) {
            return ReflectionFunction::createFromName($spec)->getParameter($parameterName);
        }

        if ($spec instanceof Closure) {
            return self::createFromClosure($spec, $parameterName);
        }

        throw new InvalidArgumentException('Could not create reflection from the spec given');
    }

    public function __toString() : string
    {
        return ReflectionParameterStringCast::toString($this);
    }

    /**
     * @internal
     * @param Reflector                  $reflector
     * @param ParamNode                  $node               Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     * @param Namespace_|null            $declaringNamespace namespace of the declaring function/method
     * @param ReflectionFunctionAbstract $function
     * @param int                        $parameterIndex
     *
     * @return ReflectionParameter
     */
    public static function createFromNode(
        Reflector $reflector,
        ParamNode $node,
        ?Namespace_ $declaringNamespace,
        ReflectionFunctionAbstract $function,
        int $parameterIndex
    ) : self {
        $param                     = new self();
        $param->reflector          = $reflector;
        $param->node               = $node;
        $param->declaringNamespace = $declaringNamespace;
        $param->function           = $function;
        $param->parameterIndex     = $parameterIndex;

        return $param;
    }

    private function parseDefaultValueNode() : void
    {
        if ( ! $this->isDefaultValueAvailable()) {
            throw new LogicException('This parameter does not have a default value available');
        }

        $defaultValueNode = $this->node->default;

        if ($defaultValueNode instanceof Node\Expr\ClassConstFetch) {
            $className = $defaultValueNode->class->toString();

            if ('self' === $className || 'static' === $className) {
                $className = $this->findParentClassDeclaringConstant($defaultValueNode->name);
            }

            $this->isDefaultValueConstant   = true;
            $this->defaultValueConstantName = $className . '::' . $defaultValueNode->name;
            $this->defaultValueConstantType = self::CONST_TYPE_CLASS;
        }

        if ($defaultValueNode instanceof Node\Expr\ConstFetch
            && ! \in_array(\strtolower($defaultValueNode->name->parts[0]), ['true', 'false', 'null'], true)) {
            $this->isDefaultValueConstant   = true;
            $this->defaultValueConstantName = $defaultValueNode->name->parts[0];
            $this->defaultValueConstantType = self::CONST_TYPE_DEFINED;
            $this->defaultValue             = null;
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
    private function findParentClassDeclaringConstant(string $constantName) : string
    {
        /** @var ReflectionMethod $method */
        $method = $this->function;
        $class  = $method->getDeclaringClass();

        do {
            if ($class->hasConstant($constantName)) {
                return $class->getName();
            }
        } while ($class = $class->getParentClass());

        // note: this code is theoretically unreachable, so don't expect any coverage on it
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
    public function getDeclaringClass() : ?ReflectionClass
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
        return ((bool) $this->node->isOptional) || $this->isVariadic();
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
        return null !== $this->node->default;
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
     * Does this method allow null for a parameter?
     *
     * @return bool
     */
    public function allowsNull() : bool
    {
        if ( ! $this->hasType()) {
            return true;
        }

        if ($this->node->type instanceof NullableType) {
            return true;
        }

        if ( ! $this->isDefaultValueAvailable()) {
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
            $stringTypes[] = (string) $type;
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
        return  (new FindParameterType())->__invoke($this->function, $this->declaringNamespace, $this->node);
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
     * Get the ReflectionType instance representing the type declaration for
     * this parameter
     *
     * (note: this has nothing to do with DocBlocks).
     *
     * @return ReflectionType|null
     */
    public function getType() : ?ReflectionType
    {
        $type = $this->node->type;

        if (null === $type) {
            return null;
        }

        if ($type instanceof NullableType) {
            $type = $type->type;
        }

        return ReflectionType::createFromType((string) $type, $this->allowsNull());
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
        return null !== $this->node->type;
    }

    /**
     * Set the parameter type declaration.
     *
     * @param string $newParameterType
     */
    public function setType(string $newParameterType) : void
    {
        $this->node->type = new Node\Name($newParameterType);
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
        return 'array' === \strtolower((string) $this->getType());
    }

    /**
     * Is this parameter a callable?
     *
     * @return bool
     */
    public function isCallable() : bool
    {
        return 'callable' === \strtolower((string) $this->getType());
    }

    /**
     * Is this parameter a variadic (denoted by ...$param).
     *
     * @return bool
     */
    public function isVariadic() : bool
    {
        return $this->node->variadic;
    }

    /**
     * Is this parameter passed by reference (denoted by &$param).
     *
     * @return bool
     */
    public function isPassedByReference() : bool
    {
        return $this->node->byRef;
    }

    /**
     * @return bool
     */
    public function canBePassedByValue() : bool
    {
        return ! $this->isPassedByReference();
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
        if ( ! $this->isDefaultValueConstant()) {
            throw new LogicException('This parameter is not a constant default value, so cannot have a constant name');
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
        $className = $this->getClassName();

        if (null === $className) {
            return null;
        }

        if ( ! $this->reflector instanceof ClassReflector) {
            throw new RuntimeException(\sprintf(
                'Unable to reflect class type because we were not given a "%s", but a "%s" instead',
                ClassReflector::class,
                \get_class($this->reflector)
            ));
        }

        return $this->reflector->reflect($className);
    }

    private function getClassName() : ?string
    {
        if ( ! $this->hasType()) {
            return null;
        }

        $type     = $this->getType();
        $typeHint = (string) $type;

        if ('self' === $typeHint) {
            return $this->getDeclaringClass()->getName();
        }

        if ('parent' === $typeHint) {
            return $this->getDeclaringClass()->getParentClass()->getName();
        }

        if ($type->isBuiltin()) {
            return null;
        }

        return $typeHint;
    }

    /**
     * {@inheritdoc}
     * @throws Uncloneable
     */
    public function __clone()
    {
        throw Uncloneable::fromClass(__CLASS__);
    }

    public function getStartColumn() : int
    {
        return CalculateReflectionColum::getStartColumn($this->function->getLocatedSource()->getSource(), $this->node);
    }

    public function getEndColumn() : int
    {
        return CalculateReflectionColum::getEndColumn($this->function->getLocatedSource()->getSource(), $this->node);
    }
}
