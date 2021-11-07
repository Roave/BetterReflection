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
use PhpParser\Node\Param as ParamNode;
use PhpParser\Node\Stmt\Namespace_;
use Roave\BetterReflection\NodeCompiler\CompiledValue;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use Roave\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\StringCast\ReflectionParameterStringCast;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\TypesFinder\FindParameterType;
use Roave\BetterReflection\Util\CalculateReflectionColumn;

use function assert;
use function count;
use function is_array;
use function is_object;
use function is_string;
use function strtolower;

class ReflectionParameter
{
    private ParamNode $node;

    private ?Namespace_ $declaringNamespace;

    private ReflectionMethod|ReflectionFunction $function;

    private int $parameterIndex;

    private ?CompiledValue $compiledDefaultValue = null;

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
        string $parameterName,
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
        string $parameterName,
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
    public static function createFromSpec(array|string|Closure $spec, string $parameterName): self
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
        ReflectionMethod|ReflectionFunction $function,
        int $parameterIndex,
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
    private function getCompiledDefaultValue(): CompiledValue
    {
        if (! $this->isDefaultValueAvailable()) {
            throw new LogicException('This parameter does not have a default value available');
        }

        if ($this->compiledDefaultValue === null) {
            $this->compiledDefaultValue = (new CompileNodeToValue())->__invoke(
                $this->node->default,
                new CompilerContext($this->reflector, $this),
            );
        }

        return $this->compiledDefaultValue;
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
    public function getDeclaringFunction(): ReflectionMethod|ReflectionFunction
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

    public function getImplementingClass(): ?ReflectionClass
    {
        if ($this->function instanceof ReflectionMethod) {
            return $this->function->getImplementingClass();
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
    public function getDefaultValue(): string|int|float|bool|array|null
    {
        return $this->getCompiledDefaultValue()->value;
    }

    /**
     * Does this method allow null for a parameter?
     */
    public function allowsNull(): bool
    {
        $type = $this->getType();

        if ($type === null) {
            return true;
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if (strtolower($unionType->getName()) === 'null') {
                    return true;
                }
            }
        }

        return $type->allowsNull();
    }

    /**
     * Get the DocBlock type hints as an array of strings.
     *
     * @return list<string>
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
     * @return list<Type>
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
    public function getType(): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null
    {
        $type = $this->node->type;
        assert($type instanceof Node\Identifier || $type instanceof Node\Name || $type instanceof Node\NullableType || $type instanceof Node\UnionType || $type instanceof Node\IntersectionType || $type === null);

        if ($type === null) {
            return null;
        }

        $allowsNull = $this->isDefaultValueAvailable() && $this->getDefaultValue() === null && ! $this->isDefaultValueConstant();

        return ReflectionType::createFromNode($this->reflector, $this, $type, $allowsNull);
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
        return $this->isType($this->getType(), 'array');
    }

    /**
     * Is this parameter a callable?
     */
    public function isCallable(): bool
    {
        return $this->isType($this->getType(), 'callable');
    }

    /**
     * For isArray() and isCallable().
     */
    private function isType(ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $typeReflection, string $type): bool
    {
        if ($typeReflection === null) {
            return false;
        }

        if ($typeReflection instanceof ReflectionIntersectionType) {
            return false;
        }

        $isOneOfAllowedTypes = static function (ReflectionNamedType $namedType, string ...$types): bool {
            foreach ($types as $type) {
                if (strtolower($namedType->getName()) === $type) {
                    return true;
                }
            }

            return false;
        };

        if ($typeReflection instanceof ReflectionUnionType) {
            $unionTypes = $typeReflection->getTypes();

            foreach ($unionTypes as $unionType) {
                if (! $isOneOfAllowedTypes($unionType, $type, 'null')) {
                    return false;
                }
            }

            return true;
        }

        return $isOneOfAllowedTypes($typeReflection, $type);
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

    public function isPromoted(): bool
    {
        return $this->node->flags !== 0;
    }

    /**
     * @throws LogicException
     */
    public function isDefaultValueConstant(): bool
    {
        return $this->getCompiledDefaultValue()->constantName !== null;
    }

    /**
     * @throws LogicException
     */
    public function getDefaultValueConstantName(): string
    {
        if (! $this->isDefaultValueConstant()) {
            throw new LogicException('This parameter is not a constant default value, so cannot have a constant name');
        }

        return $this->getCompiledDefaultValue()->constantName;
    }

    /**
     * Gets a ReflectionClass for the type hint (returns null if not a class)
     */
    public function getClass(): ?ReflectionClass
    {
        $type = $this->getType();

        if ($type === null) {
            return null;
        }

        if ($type instanceof ReflectionIntersectionType) {
            return null;
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $innerType) {
                $innerTypeClass = $this->getClassFromNamedType($innerType);
                if ($innerTypeClass !== null) {
                    return $innerTypeClass;
                }
            }

            return null;
        }

        return $this->getClassFromNamedType($type);
    }

    private function getClassFromNamedType(ReflectionNamedType $namedType): ?ReflectionClass
    {
        try {
            return $namedType->getClass();
        } catch (LogicException) {
            return null;
        }
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

    /**
     * @return list<ReflectionAttribute>
     */
    public function getAttributes(): array
    {
        return ReflectionAttributeHelper::createAttributes($this->reflector, $this);
    }

    /**
     * @return list<ReflectionAttribute>
     */
    public function getAttributesByName(string $name): array
    {
        return ReflectionAttributeHelper::filterAttributesByName($this->getAttributes(), $name);
    }

    /**
     * @param class-string $className
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributesByInstance(string $className): array
    {
        return ReflectionAttributeHelper::filterAttributesByInstance($this->getAttributes(), $className);
    }
}
