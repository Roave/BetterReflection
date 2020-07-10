<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Closure;
use Exception;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use phpDocumentor\Reflection\Type;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param as ParamNode;
use PhpParser\Node\Stmt\Namespace_;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\StringCast\ReflectionParameterStringCast;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\TypesFinder\FindParameterType;
use Roave\BetterReflection\Util\CalculateReflectionColumn;
use RuntimeException;

use function assert;
use function count;
use function get_class;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;
use function strtolower;

class ReflectionParameter
{
    private ParamNode $node;

    private ?Namespace_ $declaringNamespace;

    private ReflectionFunctionAbstract $function;

    private int $parameterIndex;

    /** @var scalar|array<scalar>|null */
    private $defaultValue;

    private bool $isDefaultValueConstant = false;

    private ?string $defaultValueConstantName;

    private Reflector $reflector;

    private function __construct()
    {
    }

    /**
     * Create a reflection of a parameter using a class name
     *
     * @throws OutOfBoundsException
     */
    public static function createFromClassNameAndMethod(
        string $className,
        string $methodName,
        string $parameterName
    ): self {
        return ReflectionClass::createFromName($className)
            ->getMethod($methodName)
            ->getParameter($parameterName);
    }

    /**
     * Create a reflection of a parameter using an instance
     *
     * @throws OutOfBoundsException
     */
    public static function createFromClassInstanceAndMethod(
        object $instance,
        string $methodName,
        string $parameterName
    ): self {
        return ReflectionClass::createFromInstance($instance)
            ->getMethod($methodName)
            ->getParameter($parameterName);
    }

    /**
     * Create a reflection of a parameter using a closure
     */
    public static function createFromClosure(Closure $closure, string $parameterName): ReflectionParameter
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
     * @param object[]|string[]|string|Closure $spec
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public static function createFromSpec($spec, string $parameterName): self
    {
        if (is_array($spec) && count($spec) === 2 && is_string($spec[1])) {
            if (is_object($spec[0])) {
                return self::createFromClassInstanceAndMethod($spec[0], $spec[1], $parameterName);
            }

            return self::createFromClassNameAndMethod($spec[0], $spec[1], $parameterName);
        }

        if (is_string($spec)) {
            return ReflectionFunction::createFromName($spec)->getParameter($parameterName);
        }

        if ($spec instanceof Closure) {
            return self::createFromClosure($spec, $parameterName);
        }

        throw new InvalidArgumentException('Could not create reflection from the spec given');
    }

    public function __toString(): string
    {
        return ReflectionParameterStringCast::toString($this);
    }

    /**
     * @internal
     *
     * @param ParamNode       $node               Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     * @param Namespace_|null $declaringNamespace namespace of the declaring function/method
     */
    public static function createFromNode(
        Reflector $reflector,
        ParamNode $node,
        ?Namespace_ $declaringNamespace,
        ReflectionFunctionAbstract $function,
        int $parameterIndex
    ): self {
        $param                     = new self();
        $param->reflector          = $reflector;
        $param->node               = $node;
        $param->declaringNamespace = $declaringNamespace;
        $param->function           = $function;
        $param->parameterIndex     = $parameterIndex;

        return $param;
    }

    /**
     * @throws LogicException
     */
    private function parseDefaultValueNode(): ?Expr
    {
        if (! $this->isDefaultValueAvailable()) {
            throw new LogicException('This parameter does not have a default value available');
        }

        $defaultValueNode = $this->node->default;

        if ($defaultValueNode instanceof Node\Expr\ClassConstFetch) {
            assert($defaultValueNode->class instanceof Node\Name);
            assert($defaultValueNode->name instanceof Node\Identifier);

            $className = $defaultValueNode->class->toString();

            if ($className === 'self' || $className === 'static') {
                $constantName = $defaultValueNode->name->name;
                $className    = $this->findParentClassDeclaringConstant($constantName);
            }

            $this->isDefaultValueConstant   = true;
            $this->defaultValueConstantName = $className . '::' . $defaultValueNode->name->name;
        } elseif (
            $defaultValueNode instanceof Node\Expr\ConstFetch
            && ! in_array(strtolower($defaultValueNode->name->parts[0]), ['true', 'false', 'null'], true)
        ) {
            $this->isDefaultValueConstant   = true;
            $this->defaultValueConstantName = $defaultValueNode->name->parts[0];
        }

        return $defaultValueNode;
    }

    /**
     * @throws LogicException
     */
    private function findParentClassDeclaringConstant(string $constantName): string
    {
        $method = $this->function;
        assert($method instanceof ReflectionMethod);
        $class = $method->getDeclaringClass();

        do {
            if ($class->hasConstant($constantName)) {
                return $class->getName();
            }

            $class = $class->getParentClass();
        } while ($class);

        // note: this code is theoretically unreachable, so don't expect any coverage on it
        throw new LogicException(sprintf('Failed to find parent class of constant "%s".', $constantName));
    }

    /**
     * Get the name of the parameter.
     */
    public function getName(): string
    {
        assert(is_string($this->node->var->name));

        return $this->node->var->name;
    }

    /**
     * Get the function (or method) that declared this parameter.
     */
    public function getDeclaringFunction(): ReflectionFunctionAbstract
    {
        return $this->function;
    }

    /**
     * Get the class from the method that this parameter belongs to, if it
     * exists.
     *
     * This will return null if the declaring function is not a method.
     */
    public function getDeclaringClass(): ?ReflectionClass
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
     */
    public function isOptional(): bool
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
     */
    public function isDefaultValueAvailable(): bool
    {
        return $this->node->default !== null;
    }

    /**
     * Get the default value of the parameter.
     *
     * @return scalar|array<scalar>|null
     *
     * @throws LogicException
     * @throws UnableToCompileNode
     */
    public function getDefaultValue()
    {
        $defaultValueNode = $this->parseDefaultValueNode();

        if ($defaultValueNode) {
            $this->defaultValue = (new CompileNodeToValue())->__invoke(
                $defaultValueNode,
                new CompilerContext($this->reflector, $this->getDeclaringClass()),
            );
        }

        return $this->defaultValue;
    }

    /**
     * Does this method allow null for a parameter?
     */
    public function allowsNull(): bool
    {
        if (! $this->hasType()) {
            return true;
        }

        if ($this->node->type instanceof NullableType) {
            return true;
        }

        if (! $this->isDefaultValueAvailable()) {
            return false;
        }

        return $this->getDefaultValue() === null;
    }

    /**
     * Get the DocBlock type hints as an array of strings.
     *
     * @return string[]
     */
    public function getDocBlockTypeStrings(): array
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
     *
     * @return Type[]
     */
    public function getDocBlockTypes(): array
    {
        return (new FindParameterType())->__invoke($this->function, $this->declaringNamespace, $this->node);
    }

    /**
     * Find the position of the parameter, left to right, starting at zero.
     */
    public function getPosition(): int
    {
        return $this->parameterIndex;
    }

    /**
     * Get the ReflectionType instance representing the type declaration for
     * this parameter
     *
     * (note: this has nothing to do with DocBlocks).
     */
    public function getType(): ?ReflectionType
    {
        $type = $this->node->type;

        if ($type === null) {
            return null;
        }

        if ($type instanceof NullableType) {
            $type = $type->type;
        }

        return ReflectionType::createFromTypeAndReflector((string) $type, $this->allowsNull(), $this->reflector);
    }

    /**
     * Does this parameter have a type declaration?
     *
     * (note: this has nothing to do with DocBlocks).
     */
    public function hasType(): bool
    {
        return $this->node->type !== null;
    }

    /**
     * Set the parameter type declaration.
     */
    public function setType(string $newParameterType): void
    {
        $this->node->type = new Node\Name($newParameterType);
    }

    /**
     * Remove the parameter type declaration completely.
     */
    public function removeType(): void
    {
        $this->node->type = null;
    }

    /**
     * Is this parameter an array?
     */
    public function isArray(): bool
    {
        return strtolower((string) $this->getType()) === 'array';
    }

    /**
     * Is this parameter a callable?
     */
    public function isCallable(): bool
    {
        return strtolower((string) $this->getType()) === 'callable';
    }

    /**
     * Is this parameter a variadic (denoted by ...$param).
     */
    public function isVariadic(): bool
    {
        return $this->node->variadic;
    }

    /**
     * Is this parameter passed by reference (denoted by &$param).
     */
    public function isPassedByReference(): bool
    {
        return $this->node->byRef;
    }

    public function canBePassedByValue(): bool
    {
        return ! $this->isPassedByReference();
    }

    /**
     * @throws LogicException
     */
    public function isDefaultValueConstant(): bool
    {
        $this->parseDefaultValueNode();

        return $this->isDefaultValueConstant;
    }

    /**
     * @throws LogicException
     */
    public function getDefaultValueConstantName(): string
    {
        $this->parseDefaultValueNode();

        if (! $this->isDefaultValueConstant) {
            throw new LogicException('This parameter is not a constant default value, so cannot have a constant name');
        }

        return $this->defaultValueConstantName;
    }

    /**
     * Gets a ReflectionClass for the type hint (returns null if not a class)
     *
     * @throws RuntimeException
     */
    public function getClass(): ?ReflectionClass
    {
        $className = $this->getClassName();

        if ($className === null) {
            return null;
        }

        if (! $this->reflector instanceof ClassReflector) {
            throw new RuntimeException(sprintf(
                'Unable to reflect class type because we were not given a "%s", but a "%s" instead',
                ClassReflector::class,
                get_class($this->reflector),
            ));
        }

        return $this->reflector->reflect($className);
    }

    private function getClassName(): ?string
    {
        if (! $this->hasType()) {
            return null;
        }

        $type = $this->getType();
        assert($type instanceof ReflectionType);
        $typeHint = (string) $type;

        if ($typeHint === 'self') {
            $declaringClass = $this->getDeclaringClass();
            assert($declaringClass instanceof ReflectionClass);

            return $declaringClass->getName();
        }

        if ($typeHint === 'parent') {
            $declaringClass = $this->getDeclaringClass();
            assert($declaringClass instanceof ReflectionClass);
            $parentClass = $declaringClass->getParentClass();
            assert($parentClass instanceof ReflectionClass);

            return $parentClass->getName();
        }

        if ($type->isBuiltin()) {
            return null;
        }

        return $typeHint;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Uncloneable
     */
    public function __clone()
    {
        throw Uncloneable::fromClass(self::class);
    }

    public function getStartColumn(): int
    {
        return CalculateReflectionColumn::getStartColumn($this->function->getLocatedSource()->getSource(), $this->node);
    }

    public function getEndColumn(): int
    {
        return CalculateReflectionColumn::getEndColumn($this->function->getLocatedSource()->getSource(), $this->node);
    }

    public function getAst(): ParamNode
    {
        return $this->node;
    }
}
