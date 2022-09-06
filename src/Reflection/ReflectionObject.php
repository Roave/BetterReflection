<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use InvalidArgumentException;
use PhpParser\Builder\Property as PropertyNodeBuilder;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\Enum_ as EnumNode;
use PhpParser\Node\Stmt\Interface_ as InterfaceNode;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\Node\Stmt\Trait_ as TraitNode;
use ReflectionException;
use ReflectionObject as CoreReflectionObject;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\AnonymousClassObjectSourceLocator;

use function array_filter;
use function array_map;
use function array_merge;
use function preg_match;

class ReflectionObject extends ReflectionClass
{
    protected function __construct(private Reflector $reflector, private ReflectionClass $reflectionClass, private object $object)
    {
    }

    /**
     * Pass an instance of an object to this method to reflect it
     *
     * @throws ReflectionException
     * @throws IdentifierNotFound
     */
    public static function createFromInstance(object $instance): ReflectionClass
    {
        $className = $instance::class;

        $betterReflection = new BetterReflection();

        if (preg_match(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX_REGEXP, $className) === 1) {
            $reflector = new DefaultReflector(new AggregateSourceLocator([
                $betterReflection->sourceLocator(),
                new AnonymousClassObjectSourceLocator(
                    $instance,
                    $betterReflection->phpParser(),
                ),
            ]));
        } else {
            $reflector = $betterReflection->reflector();
        }

        return new self($reflector, $reflector->reflectClass($className), $instance);
    }

    /**
     * Reflect on runtime properties for the current instance
     *
     * @see ReflectionClass::getProperties() for the usage of $filter
     *
     * @return array<string, ReflectionProperty>
     */
    private function getRuntimeProperties(int|null $filter = null): array
    {
        if (! $this->reflectionClass->isInstance($this->object)) {
            throw new InvalidArgumentException('Cannot reflect runtime properties of a separate class');
        }

        if ($filter !== null && ! ($filter & CoreReflectionProperty::IS_PUBLIC)) {
            return [];
        }

        // Ensure we have already cached existing properties so we can add to them
        $this->reflectionClass->getProperties();

        // Only known current way is to use internal ReflectionObject to get
        // the runtime-declared properties  :/
        $reflectionProperties = (new CoreReflectionObject($this->object))->getProperties();
        $runtimeProperties    = [];

        foreach ($reflectionProperties as $property) {
            if ($this->reflectionClass->hasProperty($property->getName())) {
                continue;
            }

            $runtimeProperties[$property->getName()] = ReflectionProperty::createFromNode(
                $this->reflector,
                $this->createPropertyNodeFromRuntimePropertyReflection($property, $this->object),
                0,
                $this,
                $this,
                false,
                false,
            );
        }

        return $runtimeProperties;
    }

    /**
     * Create an AST PropertyNode given a reflection
     *
     * Note that we don't copy across DocBlock, protected, private or static
     * because runtime properties can't have these attributes.
     */
    private function createPropertyNodeFromRuntimePropertyReflection(CoreReflectionProperty $property, object $instance): PropertyNode
    {
        $builder = new PropertyNodeBuilder($property->getName());
        $builder->setDefault($property->getValue($instance));
        $builder->makePublic();

        return $builder->getNode();
    }

    public function getShortName(): string
    {
        return $this->reflectionClass->getShortName();
    }

    public function getName(): string
    {
        return $this->reflectionClass->getName();
    }

    public function getNamespaceName(): string
    {
        return $this->reflectionClass->getNamespaceName();
    }

    public function inNamespace(): bool
    {
        return $this->reflectionClass->inNamespace();
    }

    public function getExtensionName(): string|null
    {
        return $this->reflectionClass->getExtensionName();
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods(int|null $filter = null): array
    {
        return $this->reflectionClass->getMethods($filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getImmediateMethods(int|null $filter = null): array
    {
        return $this->reflectionClass->getImmediateMethods($filter);
    }

    public function getMethod(string $methodName): ReflectionMethod
    {
        return $this->reflectionClass->getMethod($methodName);
    }

    public function hasMethod(string $methodName): bool
    {
        return $this->reflectionClass->hasMethod($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function getImmediateConstants(): array
    {
        return $this->reflectionClass->getImmediateConstants();
    }

    /**
     * {@inheritdoc}
     */
    public function getConstants(): array
    {
        return $this->reflectionClass->getConstants();
    }

    public function getConstant(string $name): string|int|float|bool|array|null
    {
        return $this->reflectionClass->getConstant($name);
    }

    public function hasConstant(string $name): bool
    {
        return $this->reflectionClass->hasConstant($name);
    }

    public function getReflectionConstant(string $name): ReflectionClassConstant|null
    {
        return $this->reflectionClass->getReflectionConstant($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getImmediateReflectionConstants(): array
    {
        return $this->reflectionClass->getImmediateReflectionConstants();
    }

    /**
     * {@inheritdoc}
     */
    public function getReflectionConstants(): array
    {
        return $this->reflectionClass->getReflectionConstants();
    }

    public function getConstructor(): ReflectionMethod
    {
        return $this->reflectionClass->getConstructor();
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(int|null $filter = null): array
    {
        return array_merge(
            $this->reflectionClass->getProperties($filter),
            $this->getRuntimeProperties($filter),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getImmediateProperties(int|null $filter = null): array
    {
        return array_merge(
            $this->reflectionClass->getImmediateProperties($filter),
            $this->getRuntimeProperties($filter),
        );
    }

    public function getProperty(string $name): ReflectionProperty|null
    {
        $runtimeProperties = $this->getRuntimeProperties();

        if (isset($runtimeProperties[$name])) {
            return $runtimeProperties[$name];
        }

        return $this->reflectionClass->getProperty($name);
    }

    public function hasProperty(string $name): bool
    {
        $runtimeProperties = $this->getRuntimeProperties();

        return isset($runtimeProperties[$name]) || $this->reflectionClass->hasProperty($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultProperties(): array
    {
        return array_map(
            static fn (ReflectionProperty $property) => $property->getDefaultValue(),
            array_filter($this->getProperties(), static fn (ReflectionProperty $property): bool => $property->isDefault()),
        );
    }

    public function getFileName(): string|null
    {
        return $this->reflectionClass->getFileName();
    }

    public function getLocatedSource(): LocatedSource
    {
        return $this->reflectionClass->getLocatedSource();
    }

    public function getStartLine(): int
    {
        return $this->reflectionClass->getStartLine();
    }

    public function getEndLine(): int
    {
        return $this->reflectionClass->getEndLine();
    }

    public function getStartColumn(): int
    {
        return $this->reflectionClass->getStartColumn();
    }

    public function getEndColumn(): int
    {
        return $this->reflectionClass->getEndColumn();
    }

    public function getParentClass(): ReflectionClass|null
    {
        return $this->reflectionClass->getParentClass();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentClassNames(): array
    {
        return $this->reflectionClass->getParentClassNames();
    }

    public function getDocComment(): string
    {
        return $this->reflectionClass->getDocComment();
    }

    public function isAnonymous(): bool
    {
        return $this->reflectionClass->isAnonymous();
    }

    public function isInternal(): bool
    {
        return $this->reflectionClass->isInternal();
    }

    public function isUserDefined(): bool
    {
        return $this->reflectionClass->isUserDefined();
    }

    public function isDeprecated(): bool
    {
        return $this->reflectionClass->isDeprecated();
    }

    public function isAbstract(): bool
    {
        return $this->reflectionClass->isAbstract();
    }

    public function isFinal(): bool
    {
        return $this->reflectionClass->isFinal();
    }

    public function isReadOnly(): bool
    {
        return $this->reflectionClass->isReadOnly();
    }

    public function getModifiers(): int
    {
        return $this->reflectionClass->getModifiers();
    }

    public function isTrait(): bool
    {
        return $this->reflectionClass->isTrait();
    }

    public function isInterface(): bool
    {
        return $this->reflectionClass->isInterface();
    }

    /**
     * {@inheritdoc}
     */
    public function getTraits(): array
    {
        return $this->reflectionClass->getTraits();
    }

    /**
     * {@inheritdoc}
     */
    public function getTraitNames(): array
    {
        return $this->reflectionClass->getTraitNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getTraitAliases(): array
    {
        return $this->reflectionClass->getTraitAliases();
    }

    /**
     * {@inheritdoc}
     */
    public function getInterfaces(): array
    {
        return $this->reflectionClass->getInterfaces();
    }

    /**
     * {@inheritdoc}
     */
    public function getImmediateInterfaces(): array
    {
        return $this->reflectionClass->getImmediateInterfaces();
    }

    /**
     * {@inheritdoc}
     */
    public function getInterfaceNames(): array
    {
        return $this->reflectionClass->getInterfaceNames();
    }

    public function isInstance(object $object): bool
    {
        return $this->reflectionClass->isInstance($object);
    }

    public function isSubclassOf(string $className): bool
    {
        return $this->reflectionClass->isSubclassOf($className);
    }

    public function implementsInterface(string $interfaceName): bool
    {
        return $this->reflectionClass->implementsInterface($interfaceName);
    }

    public function isInstantiable(): bool
    {
        return $this->reflectionClass->isInstantiable();
    }

    public function isCloneable(): bool
    {
        return $this->reflectionClass->isCloneable();
    }

    public function isIterateable(): bool
    {
        return $this->reflectionClass->isIterateable();
    }

    public function isEnum(): bool
    {
        return $this->reflectionClass->isEnum();
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticProperties(): array
    {
        return $this->reflectionClass->getStaticProperties();
    }

    public function setStaticPropertyValue(string $propertyName, mixed $value): void
    {
        $this->reflectionClass->setStaticPropertyValue($propertyName, $value);
    }

    public function getStaticPropertyValue(string $propertyName): mixed
    {
        return $this->reflectionClass->getStaticPropertyValue($propertyName);
    }

    public function getAst(): ClassNode|InterfaceNode|TraitNode|EnumNode
    {
        return $this->reflectionClass->getAst();
    }

    public function getDeclaringNamespaceAst(): Namespace_|null
    {
        return $this->reflectionClass->getDeclaringNamespaceAst();
    }

    /** @return list<ReflectionAttribute> */
    public function getAttributes(): array
    {
        return $this->reflectionClass->getAttributes();
    }

    /** @return list<ReflectionAttribute> */
    public function getAttributesByName(string $name): array
    {
        return $this->reflectionClass->getAttributesByName($name);
    }

    /**
     * @param class-string $className
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributesByInstance(string $className): array
    {
        return $this->reflectionClass->getAttributesByInstance($className);
    }
}
