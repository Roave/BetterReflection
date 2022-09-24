<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use BackedEnum;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_ as EnumNode;
use PhpParser\Node\Stmt\Interface_ as InterfaceNode;
use PhpParser\Node\Stmt\Trait_ as TraitNode;
use PhpParser\Node\Stmt\TraitUse;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Annotation\AnnotationHelper;
use Roave\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAClassReflection;
use Roave\BetterReflection\Reflection\Exception\NotAnInterfaceReflection;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\Exception\PropertyDoesNotExist;
use Roave\BetterReflection\Reflection\StringCast\ReflectionClassStringCast;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Util\CalculateReflectionColumn;
use Roave\BetterReflection\Util\GetLastDocComment;
use Stringable;
use Traversable;
use UnitEnum;

use function array_combine;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_slice;
use function array_values;
use function assert;
use function end;
use function in_array;
use function is_int;
use function is_string;
use function ltrim;
use function sha1;
use function sprintf;
use function strtolower;

class ReflectionClass implements Reflection
{
    /**
     * We cannot use CoreReflectionClass::IS_READONLY because it does not exist in PHP < 8.2.
     * Constant is public, so we can use it in tests.
     *
     * @internal
     */
    public const IS_READONLY = 65536;

    public const ANONYMOUS_CLASS_NAME_PREFIX        = 'class@anonymous';
    public const ANONYMOUS_CLASS_NAME_PREFIX_REGEXP = '~^(?:class|[\w\\\\]+)@anonymous~';
    private const ANONYMOUS_CLASS_NAME_SUFFIX       = '@anonymous';

    /** @var class-string|trait-string|null */
    private string|null $name;

    private string|null $shortName;

    private bool $isInterface;
    private bool $isTrait;
    private bool $isEnum;
    private bool $isBackedEnum;

    private int $modifiers;

    private string|null $docComment;

    /** @var list<ReflectionAttribute> */
    private array $attributes;

    /** @var positive-int */
    private int $startLine;

    /** @var positive-int */
    private int $endLine;

    /** @var positive-int */
    private int $startColumn;

    /** @var positive-int */
    private int $endColumn;

    /** @var class-string|null */
    private string|null $parentClassName;

    /** @var list<class-string> */
    private array $implementsClassNames;

    /** @var list<trait-string> */
    private array $traitClassNames;

    /** @var array<string, ReflectionClassConstant> */
    private array $immediateConstants;

    /** @var array<non-empty-string, ReflectionProperty> */
    private array $immediateProperties;

    /** @var array<non-empty-string, ReflectionMethod> */
    private array $immediateMethods;

    /** @var array{aliases: array<non-empty-string, non-empty-string>, modifiers: array<non-empty-string, int>, precedences: array<non-empty-string, non-empty-string>} */
    private array $traitsData;

    /** @var array<string, ReflectionProperty>|null */
    private array|null $cachedProperties = null;

    /** @var array<lowercase-string, ReflectionMethod>|null */
    private array|null $cachedMethods = null;

    private ReflectionClass|null $cachedParentClass = null;

    /** @internal */
    protected function __construct(
        private Reflector $reflector,
        ClassNode|InterfaceNode|TraitNode|EnumNode $node,
        private LocatedSource $locatedSource,
        private string|null $namespace = null,
    ) {
        $this->name      = null;
        $this->shortName = null;
        if ($node->name instanceof Node\Identifier) {
            $namespacedName = $node->namespacedName;
            assert($namespacedName instanceof Node\Name);
            /** @psalm-var class-string|trait-string */
            $name = $namespacedName->toString();

            $this->name      = $name;
            $this->shortName = $node->name->name;
        }

        $this->isInterface  = $node instanceof InterfaceNode;
        $this->isTrait      = $node instanceof TraitNode;
        $this->isEnum       = $node instanceof EnumNode;
        $this->isBackedEnum = $node instanceof EnumNode && $node->scalarType !== null;

        $this->modifiers  = $this->computeModifiers($node);
        $this->docComment = GetLastDocComment::forNode($node);
        $this->attributes = ReflectionAttributeHelper::createAttributes($reflector, $this, $node->attrGroups);

        $startLine = $node->getStartLine();
        assert($startLine > 0);
        $endLine = $node->getEndLine();
        assert($endLine > 0);

        $this->startLine   = $startLine;
        $this->endLine     = $endLine;
        $this->startColumn = CalculateReflectionColumn::getStartColumn($locatedSource->getSource(), $node);
        $this->endColumn   = CalculateReflectionColumn::getEndColumn($locatedSource->getSource(), $node);

        /** @var class-string|null $parentClassName */
        $parentClassName       = $node instanceof ClassNode ? $node->extends?->toString() : null;
        $this->parentClassName = $parentClassName;

        // @infection-ignore-all UnwrapArrayMap: It works without array_map() as well but this is less magical
        /** @var list<class-string> $implementsClassNames */
        $implementsClassNames       = array_map(
            static fn (Node\Name $name): string => $name->toString(),
            $node instanceof TraitNode ? [] : ($node instanceof InterfaceNode ? $node->extends : $node->implements),
        );
        $this->implementsClassNames = $implementsClassNames;

        /** @var list<trait-string> $traitClassNames */
        $traitClassNames = array_merge(
            [],
            ...array_map(
                // @infection-ignore-all UnwrapArrayMap: It works without array_map() as well but this is less magical
                static fn (TraitUse $traitUse): array => array_map(static fn (Node\Name $traitName): string => $traitName->toString(), $traitUse->traits),
                $node->getTraitUses(),
            ),
        );
        $this->traitClassNames = $traitClassNames;

        $this->immediateConstants  = $this->createImmediateConstants($node, $reflector);
        $this->immediateProperties = $this->createImmediateProperties($node, $reflector);
        $this->immediateMethods    = $this->createImmediateMethods($node, $reflector);

        $this->traitsData = $this->computeTraitsData($node);
    }

    public function __toString(): string
    {
        return ReflectionClassStringCast::toString($this);
    }

    /**
     * Create a ReflectionClass by name, using default reflectors etc.
     *
     * @throws IdentifierNotFound
     */
    public static function createFromName(string $className): self
    {
        return (new BetterReflection())->reflector()->reflectClass($className);
    }

    /**
     * Create a ReflectionClass from an instance, using default reflectors etc.
     *
     * This is simply a helper method that calls ReflectionObject::createFromInstance().
     *
     * @see ReflectionObject::createFromInstance
     *
     * @throws IdentifierNotFound
     * @throws ReflectionException
     */
    public static function createFromInstance(object $instance): self
    {
        return ReflectionObject::createFromInstance($instance);
    }

    /**
     * Create from a Class Node.
     *
     * @internal
     *
     * @param ClassNode|InterfaceNode|TraitNode|EnumNode $node      Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     * @param string|null                                $namespace optional - if omitted, we assume it is global namespaced class
     */
    public static function createFromNode(
        Reflector $reflector,
        ClassNode|InterfaceNode|TraitNode|EnumNode $node,
        LocatedSource $locatedSource,
        string|null $namespace = null,
    ): self {
        return new self($reflector, $node, $locatedSource, $namespace);
    }

    /**
     * Get the "short" name of the class (e.g. for A\B\Foo, this will return
     * "Foo").
     */
    public function getShortName(): string
    {
        if ($this->shortName !== null) {
            return $this->shortName;
        }

        $fileName = $this->getFileName();

        if ($fileName === null) {
            $fileName = sha1($this->locatedSource->getSource());
        }

        return sprintf('%s%s%c%s(%d)', $this->getAnonymousClassNamePrefix(), self::ANONYMOUS_CLASS_NAME_SUFFIX, "\0", $fileName, $this->getStartLine());
    }

    /**
     * PHP creates the name of the anonymous class based on first parent
     * or implemented interface.
     */
    private function getAnonymousClassNamePrefix(): string
    {
        $parentClassNames = $this->getParentClassNames();
        if ($parentClassNames !== []) {
            return $parentClassNames[0];
        }

        $interfaceNames = $this->getInterfaceNames();
        if ($interfaceNames !== []) {
            return $interfaceNames[0];
        }

        return 'class';
    }

    /**
     * Get the "full" name of the class (e.g. for A\B\Foo, this will return
     * "A\B\Foo").
     *
     * @return class-string|trait-string
     */
    public function getName(): string
    {
        if (! $this->inNamespace()) {
            /** @psalm-var class-string|trait-string */
            return $this->getShortName();
        }

        assert($this->name !== null);

        return $this->name;
    }

    /**
     * Get the "namespace" name of the class (e.g. for A\B\Foo, this will
     * return "A\B").
     */
    public function getNamespaceName(): string|null
    {
        return $this->namespace;
    }

    /**
     * Decide if this class is part of a namespace. Returns false if the class
     * is in the global namespace or does not have a specified namespace.
     */
    public function inNamespace(): bool
    {
        return $this->namespace !== null;
    }

    public function getExtensionName(): string|null
    {
        return $this->locatedSource->getExtensionName();
    }

    /** @return list<ReflectionMethod> */
    private function createMethodsFromTrait(ReflectionMethod $method): array
    {
        $methodModifiers = $method->getModifiers();
        $methodHash      = $this->methodHash($method->getImplementingClass()->getName(), $method->getName());

        if (array_key_exists($methodHash, $this->traitsData['modifiers'])) {
            // PhpParser modifiers are compatible with PHP reflection modifiers
            $methodModifiers = ($methodModifiers & ~ Node\Stmt\Class_::VISIBILITY_MODIFIER_MASK) | $this->traitsData['modifiers'][$methodHash];
        }

        $createMethod = function (string|null $aliasMethodName) use ($method, $methodModifiers): ReflectionMethod {
            assert($aliasMethodName === null || $aliasMethodName !== '');

            /** @psalm-suppress ArgumentTypeCoercion */
            return $method->withImplementingClass($this, $aliasMethodName, $methodModifiers);
        };

        $methods = [];
        foreach ($this->traitsData['aliases'] as $aliasMethodName => $traitAliasDefinition) {
            if ($methodHash !== $traitAliasDefinition) {
                continue;
            }

            $methods[] = $createMethod($aliasMethodName);
        }

        if (! array_key_exists($methodHash, $this->traitsData['precedences'])) {
            $methods[] = $createMethod($method->getAliasName());
        }

        return $methods;
    }

    /** @return list<ReflectionMethod> */
    private function getParentMethods(): array
    {
        return array_merge(
            [],
            ...array_map(
                function (ReflectionClass $ancestor): array {
                    return array_map(
                        fn (ReflectionMethod $method): ReflectionMethod => $method->withCurrentClass($this),
                        $ancestor->getMethods(),
                    );
                },
                array_filter([$this->getParentClass()]),
            ),
        );
    }

    /** @return list<ReflectionMethod> */
    private function getMethodsFromTraits(): array
    {
        return array_merge(
            [],
            ...array_map(
                function (ReflectionClass $trait): array {
                    return array_merge(
                        [],
                        ...array_map(
                            fn (ReflectionMethod $method): array => $this->createMethodsFromTrait($method),
                            $trait->getMethods(),
                        ),
                    );
                },
                $this->getTraits(),
            ),
        );
    }

    /** @return list<ReflectionMethod> */
    private function getMethodsFromInterfaces(): array
    {
        return array_merge(
            [],
            ...array_map(
                static fn (ReflectionClass $ancestor): array => $ancestor->getMethods(),
                array_values($this->getInterfaces()),
            ),
        );
    }

    /**
     * Construct a flat list of all methods in this precise order from:
     *  - current class
     *  - parent class
     *  - traits used in parent class
     *  - interfaces implemented in parent class
     *  - traits used in current class
     *  - interfaces implemented in current class
     *
     * Methods are not merged via their name as array index, since internal PHP method
     * sorting does not follow `\array_merge()` semantics.
     *
     * @return array<lowercase-string, ReflectionMethod> indexed by method name
     */
    private function getMethodsIndexedByName(): array
    {
        if ($this->cachedMethods !== null) {
            return $this->cachedMethods;
        }

        $classMethods     = $this->getImmediateMethods();
        $className        = $this->getName();
        $parentMethods    = $this->getParentMethods();
        $traitsMethods    = $this->getMethodsFromTraits();
        $interfaceMethods = $this->getMethodsFromInterfaces();

        $methods = [];

        foreach ([$classMethods, $parentMethods, 'traits' => $traitsMethods, $interfaceMethods] as $type => $typeMethods) {
            foreach ($typeMethods as $method) {
                $methodName = strtolower($method->getName());

                if (! array_key_exists($methodName, $methods)) {
                    $methods[$methodName] = $method;
                    continue;
                }

                if ($type !== 'traits') {
                    continue;
                }

                $existingMethod = $methods[$methodName];

                // Non-abstract trait method can overwrite existing method:
                // - when existing method comes from parent class
                // - when existing method comes from trait and is abstract

                if ($method->isAbstract()) {
                    continue;
                }

                if (
                    $existingMethod->getDeclaringClass()->getName() === $className
                    && ! (
                        $existingMethod->isAbstract()
                        && $existingMethod->getDeclaringClass()->isTrait()
                    )
                ) {
                    continue;
                }

                $methods[$methodName] = $method;
            }
        }

        $this->cachedMethods = $methods;

        return $this->cachedMethods;
    }

    /**
     * Fetch an array of all methods for this class.
     *
     * Filter the results to include only methods with certain attributes. Defaults
     * to no filtering.
     * Any combination of \ReflectionMethod::IS_STATIC,
     * \ReflectionMethod::IS_PUBLIC,
     * \ReflectionMethod::IS_PROTECTED,
     * \ReflectionMethod::IS_PRIVATE,
     * \ReflectionMethod::IS_ABSTRACT,
     * \ReflectionMethod::IS_FINAL.
     * For example if $filter = \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_FINAL
     * the only the final public methods will be returned
     *
     * @return list<ReflectionMethod>
     */
    public function getMethods(int|null $filter = null): array
    {
        if ($filter === null) {
            return array_values($this->getMethodsIndexedByName());
        }

        return array_values(
            array_filter(
                $this->getMethodsIndexedByName(),
                static fn (ReflectionMethod $method): bool => (bool) ($filter & $method->getModifiers()),
            ),
        );
    }

    /**
     * Get only the methods that this class implements (i.e. do not search
     * up parent classes etc.)
     *
     * @see ReflectionClass::getMethods for the usage of $filter
     *
     * @return array<non-empty-string, ReflectionMethod>
     */
    public function getImmediateMethods(int|null $filter = null): array
    {
        if ($filter === null) {
            return $this->immediateMethods;
        }

        return array_filter(
            $this->immediateMethods,
            static fn (ReflectionMethod $method): bool => (bool) ($filter & $method->getModifiers()),
        );
    }

    /** @return array<non-empty-string, ReflectionMethod> */
    private function createImmediateMethods(ClassNode|InterfaceNode|TraitNode|EnumNode $node, Reflector $reflector): array
    {
        $methods = [];

        foreach ($node->getMethods() as $methodNode) {
            $method = ReflectionMethod::createFromNode(
                $reflector,
                $methodNode,
                $this->locatedSource,
                $this->getNamespaceName(),
                $this,
                $this,
                $this,
            );

            $methods[$method->getName()] = $method;
        }

        if ($node instanceof EnumNode) {
            $methods = $this->addEnumMethods($node, $methods);
        }

        return $methods;
    }

    /**
     * @param array<non-empty-string, ReflectionMethod> $methods
     *
     * @return array<non-empty-string, ReflectionMethod>
     */
    private function addEnumMethods(EnumNode $node, array $methods): array
    {
        $internalLocatedSource = new InternalLocatedSource('', $this->getName(), 'Core');
        $createMethod          = fn (string $name, array $params, Node\Identifier|Node\NullableType $returnType): ReflectionMethod => ReflectionMethod::createFromNode(
            $this->reflector,
            new ClassMethod(
                new Node\Identifier($name),
                [
                    'flags' => ClassNode::MODIFIER_PUBLIC | ClassNode::MODIFIER_STATIC,
                    'params' => $params,
                    'returnType' => $returnType,
                ],
            ),
            $internalLocatedSource,
            $this->getNamespaceName(),
            $this,
            $this,
            $this,
        );

        $methods['cases'] = $createMethod('cases', [], new Node\Identifier('array'));

        if ($node->scalarType === null) {
            return $methods;
        }

        $valueParameter = new Node\Param(
            new Node\Expr\Variable('value'),
            null,
            new Node\UnionType([new Node\Identifier('string'), new Node\Identifier('int')]),
        );

        $methods['from'] = $createMethod(
            'from',
            [$valueParameter],
            new Node\Identifier('static'),
        );

        $methods['tryFrom'] = $createMethod(
            'tryFrom',
            [$valueParameter],
            new Node\NullableType(new Node\Identifier('static')),
        );

        return $methods;
    }

    /**
     * Get a single method with the name $methodName.
     *
     * @param non-empty-string $methodName
     */
    public function getMethod(string $methodName): ReflectionMethod|null
    {
        $lowercaseMethodName = strtolower($methodName);
        $methods             = $this->getMethodsIndexedByName();

        return $methods[$lowercaseMethodName] ?? null;
    }

    /**
     * Does the class have the specified method?
     *
     * @param non-empty-string $methodName
     */
    public function hasMethod(string $methodName): bool
    {
        return $this->getMethod($methodName) !== null;
    }

    /**
     * Get an associative array of only the constants for this specific class (i.e. do not search
     * up parent classes etc.), with keys as constant names and values as {@see ReflectionClassConstant} objects.
     *
     * @return array<string, ReflectionClassConstant> indexed by name
     */
    public function getImmediateConstants(int|null $filter = null): array
    {
        if ($filter === null) {
            return $this->immediateConstants;
        }

        return array_filter(
            $this->immediateConstants,
            static fn (ReflectionClassConstant $constant): bool => (bool) ($filter & $constant->getModifiers()),
        );
    }

    /**
     * Does this class have the specified constant?
     */
    public function hasConstant(string $name): bool
    {
        return $this->getConstant($name) !== null;
    }

    /**
     * Get the reflection object of the specified class constant.
     *
     * Returns null if not specified.
     */
    public function getConstant(string $name): ReflectionClassConstant|null
    {
        return $this->getConstants()[$name] ?? null;
    }

    /** @return array<string, ReflectionClassConstant> */
    private function createImmediateConstants(ClassNode|InterfaceNode|TraitNode|EnumNode $node, Reflector $reflector): array
    {
        $constants = [];

        foreach ($node->getConstants() as $constantsNode) {
            foreach (array_keys($constantsNode->consts) as $constantPositionInNode) {
                assert(is_int($constantPositionInNode));
                $constant = ReflectionClassConstant::createFromNode($reflector, $constantsNode, $constantPositionInNode, $this, $this);

                $constants[$constant->getName()] = $constant;
            }
        }

        return $constants;
    }

    /**
     * Get an associative array of the defined constants in this class,
     * with keys as constant names and values as {@see ReflectionClassConstant} objects.
     *
     * @return array<string, ReflectionClassConstant> indexed by name
     */
    public function getConstants(int|null $filter = null): array
    {
        // Note: constants are not merged via their name as array index, since internal PHP constant
        //       sorting does not follow `\array_merge()` semantics
        $allReflectionConstants = array_merge(
            array_values($this->getImmediateConstants()),
            ...array_map(
                static function (ReflectionClass $ancestor): array {
                    return array_values(array_filter(
                        $ancestor->getConstants(),
                        static fn (ReflectionClassConstant $classConstant): bool => ! $classConstant->isPrivate(),
                    ));
                },
                array_filter([$this->getParentClass()]),
            ),
            ...array_map(
                function (ReflectionClass $trait) {
                    return array_map(
                        fn (ReflectionClassConstant $classConstant): ReflectionClassConstant => $classConstant->withImplementingClass($this),
                        $trait->getConstants(),
                    );
                },
                $this->getTraits(),
            ),
            ...array_map(
                static fn (ReflectionClass $interface): array => array_values($interface->getConstants()),
                array_values($this->getInterfaces()),
            ),
        );

        $reflectionConstants = [];

        foreach ($allReflectionConstants as $constant) {
            $constantName = $constant->getName();

            if (isset($reflectionConstants[$constantName])) {
                continue;
            }

            $reflectionConstants[$constantName] = $constant;
        }

        if ($filter === null) {
            return $reflectionConstants;
        }

        return array_filter(
            $reflectionConstants,
            static fn (ReflectionClassConstant $constant): bool => (bool) ($filter & $constant->getModifiers()),
        );
    }

    /**
     * Get the constructor method for this class.
     */
    public function getConstructor(): ReflectionMethod|null
    {
        $constructors = array_values(array_filter($this->getMethods(), static fn (ReflectionMethod $method): bool => $method->isConstructor()));

        return $constructors[0] ?? null;
    }

    /**
     * Get only the properties for this specific class (i.e. do not search
     * up parent classes etc.)
     *
     * @see ReflectionClass::getProperties() for the usage of filter
     *
     * @return array<non-empty-string, ReflectionProperty>
     */
    public function getImmediateProperties(int|null $filter = null): array
    {
        if ($filter === null) {
            return $this->immediateProperties;
        }

        return array_filter(
            $this->immediateProperties,
            static fn (ReflectionProperty $property): bool => (bool) ($filter & $property->getModifiers()),
        );
    }

    /** @return array<non-empty-string, ReflectionProperty> */
    private function createImmediateProperties(ClassNode|InterfaceNode|TraitNode|EnumNode $node, Reflector $reflector): array
    {
        $properties = [];

        foreach ($node->getProperties() as $propertiesNode) {
            foreach (array_keys($propertiesNode->props) as $propertyPositionInNode) {
                assert(is_int($propertyPositionInNode));

                $property                         = ReflectionProperty::createFromNode(
                    $reflector,
                    $propertiesNode,
                    $propertyPositionInNode,
                    $this,
                    $this,
                );
                $properties[$property->getName()] = $property;
            }
        }

        foreach ($node->getMethods() as $methodNode) {
            if ($methodNode->name->toLowerString() !== '__construct') {
                continue;
            }

            foreach ($methodNode->params as $parameterNode) {
                if ($parameterNode->flags === 0) {
                    // No flags, no promotion
                    continue;
                }

                $parameterNameNode = $parameterNode->var;
                assert($parameterNameNode instanceof Node\Expr\Variable);
                assert(is_string($parameterNameNode->name));

                $propertyNode                     = new Node\Stmt\Property(
                    $parameterNode->flags,
                    [new Node\Stmt\PropertyProperty($parameterNameNode->name)],
                    $parameterNode->getAttributes(),
                    $parameterNode->type,
                    $parameterNode->attrGroups,
                );
                $property                         = ReflectionProperty::createFromNode(
                    $reflector,
                    $propertyNode,
                    0,
                    $this,
                    $this,
                    true,
                );
                $properties[$property->getName()] = $property;
            }
        }

        if ($node instanceof EnumNode || $node instanceof InterfaceNode) {
            $properties = $this->addEnumProperties($properties, $node, $reflector);
        }

        return $properties;
    }

    /**
     * @param array<non-empty-string, ReflectionProperty> $properties
     *
     * @return array<non-empty-string, ReflectionProperty>
     */
    private function addEnumProperties(array $properties, EnumNode|InterfaceNode $node, Reflector $reflector): array
    {
        $createProperty = function (string $name, string|Node\Identifier|Node\UnionType $type) use ($reflector): ReflectionProperty {
            $propertyNode = new Node\Stmt\Property(
                ClassNode::MODIFIER_PUBLIC | ClassNode::MODIFIER_READONLY,
                [new Node\Stmt\PropertyProperty($name)],
                [],
                $type,
            );

            return ReflectionProperty::createFromNode(
                $reflector,
                $propertyNode,
                0,
                $this,
                $this,
            );
        };

        if ($node instanceof InterfaceNode) {
            $interfaceName = $this->getName();
            if ($interfaceName === 'UnitEnum') {
                $properties['name'] = $createProperty('name', 'string');
            }

            if ($interfaceName === 'BackedEnum') {
                $properties['value'] = $createProperty('value', new Node\UnionType([
                    new Node\Identifier('int'),
                    new Node\Identifier('string'),
                ]));
            }
        } else {
            $properties['name'] = $createProperty('name', 'string');

            if ($node->scalarType !== null) {
                $properties['value'] = $createProperty('value', $node->scalarType);
            }
        }

        return $properties;
    }

    /**
     * Get the properties for this class.
     *
     * Filter the results to include only properties with certain attributes. Defaults
     * to no filtering.
     * Any combination of \ReflectionProperty::IS_STATIC,
     * \ReflectionProperty::IS_PUBLIC,
     * \ReflectionProperty::IS_PROTECTED,
     * \ReflectionProperty::IS_PRIVATE.
     * For example if $filter = \ReflectionProperty::IS_STATIC | \ReflectionProperty::IS_PUBLIC
     * only the static public properties will be returned
     *
     * @return array<string, ReflectionProperty>
     */
    public function getProperties(int|null $filter = null): array
    {
        if ($this->cachedProperties === null) {
            // merging together properties from parent class, traits, current class (in this precise order)
            $this->cachedProperties = array_merge(
                array_merge(
                    [],
                    ...array_map(
                        static function (ReflectionClass $ancestor) use ($filter): array {
                            return array_filter(
                                $ancestor->getProperties($filter),
                                static fn (ReflectionProperty $property): bool => ! $property->isPrivate(),
                            );
                        },
                        array_merge(array_filter([$this->getParentClass()]), array_values($this->getInterfaces())),
                    ),
                    ...array_map(
                        function (ReflectionClass $trait) use ($filter) {
                            return array_map(
                                fn (ReflectionProperty $property): ReflectionProperty => $property->withImplementingClass($this),
                                $trait->getProperties($filter),
                            );
                        },
                        $this->getTraits(),
                    ),
                ),
                $this->getImmediateProperties(),
            );
        }

        if ($filter === null) {
            return $this->cachedProperties;
        }

        return array_filter(
            $this->cachedProperties,
            static fn (ReflectionProperty $property): bool => (bool) ($filter & $property->getModifiers()),
        );
    }

    /**
     * Get the property called $name.
     *
     * Returns null if property does not exist.
     *
     * @param non-empty-string $name
     */
    public function getProperty(string $name): ReflectionProperty|null
    {
        $properties = $this->getProperties();

        if (! isset($properties[$name])) {
            return null;
        }

        return $properties[$name];
    }

    /**
     * Does this class have the specified property?
     *
     * @param non-empty-string $name
     */
    public function hasProperty(string $name): bool
    {
        return $this->getProperty($name) !== null;
    }

    /** @return array<string, scalar|array<scalar>|null> */
    public function getDefaultProperties(): array
    {
        return array_map(
            static fn (ReflectionProperty $property) => $property->getDefaultValue(),
            $this->getProperties(),
        );
    }

    public function getFileName(): string|null
    {
        return $this->locatedSource->getFileName();
    }

    public function getLocatedSource(): LocatedSource
    {
        return $this->locatedSource;
    }

    /**
     * Get the line number that this class starts on.
     *
     * @return positive-int
     */
    public function getStartLine(): int
    {
        return $this->startLine;
    }

    /**
     * Get the line number that this class ends on.
     *
     * @return positive-int
     */
    public function getEndLine(): int
    {
        return $this->endLine;
    }

    /** @return positive-int */
    public function getStartColumn(): int
    {
        return $this->startColumn;
    }

    /** @return positive-int */
    public function getEndColumn(): int
    {
        return $this->endColumn;
    }

    /**
     * Get the parent class, if it is defined. If this class does not have a
     * specified parent class, this will throw an exception.
     *
     * @throws NotAClassReflection
     */
    public function getParentClass(): ReflectionClass|null
    {
        if ($this->parentClassName === null) {
            return null;
        }

        if ($this->cachedParentClass === null) {
            $this->cachedParentClass = $this->reflector->reflectClass($this->parentClassName);
        }

        if ($this->cachedParentClass->isInterface() || $this->cachedParentClass->isTrait()) {
            throw NotAClassReflection::fromReflectionClass($this->cachedParentClass);
        }

        return $this->cachedParentClass;
    }

    /**
     * Gets the parent class names.
     *
     * @return list<class-string> A numerical array with parent class names as the values.
     */
    public function getParentClassNames(): array
    {
        return array_map(static fn (self $parentClass): string => $parentClass->getName(), array_slice(array_reverse($this->getInheritanceClassHierarchy()), 1));
    }

    public function getDocComment(): string|null
    {
        return $this->docComment;
    }

    public function isAnonymous(): bool
    {
        return $this->name === null;
    }

    /**
     * Is this an internal class?
     */
    public function isInternal(): bool
    {
        return $this->locatedSource->isInternal();
    }

    /**
     * Is this a user-defined function (will always return the opposite of
     * whatever isInternal returns).
     */
    public function isUserDefined(): bool
    {
        return ! $this->isInternal();
    }

    public function isDeprecated(): bool
    {
        return AnnotationHelper::isDeprecated($this->docComment);
    }

    /**
     * Is this class an abstract class.
     */
    public function isAbstract(): bool
    {
        return ($this->modifiers & CoreReflectionClass::IS_EXPLICIT_ABSTRACT) === CoreReflectionClass::IS_EXPLICIT_ABSTRACT;
    }

    /**
     * Is this class a final class.
     */
    public function isFinal(): bool
    {
        if ($this->isEnum) {
            return true;
        }

        return ($this->modifiers & CoreReflectionClass::IS_FINAL) === CoreReflectionClass::IS_FINAL;
    }

    public function isReadOnly(): bool
    {
        return ($this->modifiers & self::IS_READONLY) === self::IS_READONLY;
    }

    /**
     * Get the core-reflection-compatible modifier values.
     */
    public function getModifiers(): int
    {
        return $this->modifiers;
    }

    private function computeModifiers(ClassNode|InterfaceNode|TraitNode|EnumNode $node): int
    {
        if (! $node instanceof ClassNode) {
            return 0;
        }

        $modifiers  = $node->isAbstract() ? CoreReflectionClass::IS_EXPLICIT_ABSTRACT : 0;
        $modifiers += $node->isFinal() ? CoreReflectionClass::IS_FINAL : 0;
        $modifiers += $node->isReadonly() ? self::IS_READONLY : 0;

        return $modifiers;
    }

    /**
     * Is this reflection a trait?
     */
    public function isTrait(): bool
    {
        return $this->isTrait;
    }

    /**
     * Is this reflection an interface?
     */
    public function isInterface(): bool
    {
        return $this->isInterface;
    }

    /**
     * Get the traits used, if any are defined. If this class does not have any
     * defined traits, this will return an empty array.
     *
     * @return list<ReflectionClass>
     */
    public function getTraits(): array
    {
        return array_map(
            fn (string $traitClassName): ReflectionClass => $this->reflector->reflectClass($traitClassName),
            $this->traitClassNames,
        );
    }

    /**
     * @param array<class-string, self> $interfaces
     *
     * @return array<class-string, self>
     */
    private function addStringableInterface(array $interfaces): array
    {
        /** @psalm-var class-string $stringableClassName */
        $stringableClassName = Stringable::class;

        if (array_key_exists($stringableClassName, $interfaces)) {
            return $interfaces;
        }

        foreach (array_keys($this->immediateMethods) as $immediateMethodName) {
            if (strtolower($immediateMethodName) === '__tostring') {
                try {
                    $stringableInterfaceReflection = $this->reflector->reflectClass($stringableClassName);

                    if ($stringableInterfaceReflection->isInternal()) {
                        $interfaces[$stringableClassName] = $stringableInterfaceReflection;
                    }
                } catch (IdentifierNotFound) {
                    // Stringable interface does not exist on target PHP version
                }

                // @infection-ignore-all Break_: There's no difference between break and continue - break is just optimization
                break;
            }
        }

        return $interfaces;
    }

    /**
     * @param array<class-string, self> $interfaces
     *
     * @return array<class-string, self>
     */
    private function addEnumInterfaces(array $interfaces): array
    {
        assert($this->isEnum === true);

        $interfaces[UnitEnum::class] = $this->reflector->reflectClass(UnitEnum::class);

        if ($this->isBackedEnum) {
            $interfaces[BackedEnum::class] = $this->reflector->reflectClass(BackedEnum::class);
        }

        return $interfaces;
    }

    /**
     * Get the names of the traits used as an array of strings, if any are
     * defined. If this class does not have any defined traits, this will
     * return an empty array.
     *
     * @return list<trait-string>
     */
    public function getTraitNames(): array
    {
        return array_map(
            static function (ReflectionClass $trait): string {
                /** @psalm-var trait-string $traitName */
                $traitName = $trait->getName();

                return $traitName;
            },
            $this->getTraits(),
        );
    }

    /**
     * Return a list of the aliases used when importing traits for this class.
     * The returned array is in key/value pair in this format:.
     *
     *   'aliasedMethodName' => 'ActualClass::actualMethod'
     *
     * @return array<non-empty-string, non-empty-string>
     *
     * @example
     * // When reflecting a class such as:
     * class Foo
     * {
     *     use MyTrait {
     *         myTraitMethod as myAliasedMethod;
     *     }
     * }
     * // This method would return
     * //   ['myAliasedMethod' => 'MyTrait::myTraitMethod']
     */
    public function getTraitAliases(): array
    {
        return $this->traitsData['aliases'];
    }

    /**
     * Return a list of the precedences used when importing traits for this class.
     * The returned array is in key/value pair in this format:.
     *
     *   'Class::method' => 'Class::method'
     *
     * @return array<string, string>
     *
     * @example
     * // When reflecting a class such as:
     * class Foo
     * {
     *     use MyTrait, MyTrait2 {
     *         MyTrait2::foo insteadof MyTrait1;
     *     }
     * }
     * // This method would return
     * //   ['MyTrait1::foo' => 'MyTrait2::foo']
     */
    private function getTraitPrecedences(): array
    {
        return $this->traitsData['precedences'];
    }

    /**
     * Return a list of the used modifiers when importing traits for this class.
     * The returned array is in key/value pair in this format:.
     *
     *   'methodName' => 'modifier'
     *
     * @return array<string, int>
     *
     * @example
     * // When reflecting a class such as:
     * class Foo
     * {
     *     use MyTrait {
     *         myTraitMethod as public;
     *     }
     * }
     * // This method would return
     * //   ['myTraitMethod' => 1]
     */
    private function getTraitModifiers(): array
    {
        return $this->traitsData['modifiers'];
    }

    /** @return array{aliases: array<non-empty-string, non-empty-string>, modifiers: array<non-empty-string, int>, precedences: array<non-empty-string, non-empty-string>} */
    private function computeTraitsData(ClassNode|InterfaceNode|TraitNode|EnumNode $node): array
    {
        $traitsData = [
            'aliases'     => [],
            'modifiers'   => [],
            'precedences' => [],
        ];

        foreach ($node->getTraitUses() as $traitUsage) {
            $traitNames  = $traitUsage->traits;
            $adaptations = $traitUsage->adaptations;

            foreach ($adaptations as $adaptation) {
                $usedTrait = $adaptation->trait;
                if ($usedTrait === null) {
                    $usedTrait = end($traitNames);
                }

                $methodHash = $this->methodHash($usedTrait->toString(), $adaptation->method->toString());

                if ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Alias) {
                    if ($adaptation->newModifier) {
                        $traitsData['modifiers'][$methodHash] = $adaptation->newModifier;
                    }

                    if ($adaptation->newName) {
                        $adaptationName = $adaptation->newName->name;
                        assert($adaptationName !== '');

                        $traitsData['aliases'][$adaptationName] = $methodHash;
                        continue;
                    }
                }

                if (! $adaptation instanceof Node\Stmt\TraitUseAdaptation\Precedence || ! $adaptation->insteadof) {
                    continue;
                }

                foreach ($adaptation->insteadof as $insteadof) {
                    $adaptationNameHash = $this->methodHash($insteadof->toString(), $adaptation->method->toString());

                    $traitsData['precedences'][$adaptationNameHash] = $methodHash;
                }
            }
        }

        return $traitsData;
    }

    /**
     * @return non-empty-string
     *
     * @psalm-pure
     */
    private function methodHash(string $className, string $methodName): string
    {
        $hash = sprintf(
            '%s::%s',
            $className,
            strtolower($methodName),
        );
        assert($hash !== '');

        return $hash;
    }

    /**
     * Gets the interfaces.
     *
     * @link https://php.net/manual/en/reflectionclass.getinterfaces.php
     *
     * @return array<class-string, self> An associative array of interfaces, with keys as interface names and the array
     *                                        values as {@see ReflectionClass} objects.
     */
    public function getInterfaces(): array
    {
        return array_merge(...array_map(
            static fn (self $reflectionClass): array => $reflectionClass->getCurrentClassImplementedInterfacesIndexedByName(),
            $this->getInheritanceClassHierarchy(),
        ));
    }

    /**
     * Get only the interfaces that this class implements (i.e. do not search
     * up parent classes etc.)
     *
     * @return array<class-string, self>
     */
    public function getImmediateInterfaces(): array
    {
        if ($this->isTrait) {
            return [];
        }

        $interfaces = array_combine(
            $this->implementsClassNames,
            array_map(
                fn (string $interfaceClassName): ReflectionClass => $this->reflector->reflectClass($interfaceClassName),
                $this->implementsClassNames,
            ),
        );

        if ($this->isEnum) {
            $interfaces = $this->addEnumInterfaces($interfaces);
        }

        return $this->addStringableInterface($interfaces);
    }

    /**
     * Gets the interface names.
     *
     * @link https://php.net/manual/en/reflectionclass.getinterfacenames.php
     *
     * @return list<class-string> A numerical array with interface names as the values.
     */
    public function getInterfaceNames(): array
    {
        return array_values(array_map(
            static fn (self $interface): string => $interface->getName(),
            $this->getInterfaces(),
        ));
    }

    /**
     * Checks whether the given object is an instance.
     *
     * @link https://php.net/manual/en/reflectionclass.isinstance.php
     */
    public function isInstance(object $object): bool
    {
        $className = $this->getName();

        // note: since $object was loaded, we can safely assume that $className is available in the current
        //       php script execution context
        return $object instanceof $className;
    }

    /**
     * Checks whether the given class string is a subclass of this class.
     *
     * @link https://php.net/manual/en/reflectionclass.isinstance.php
     */
    public function isSubclassOf(string $className): bool
    {
        return in_array(
            ltrim($className, '\\'),
            array_map(
                static fn (self $reflectionClass): string => $reflectionClass->getName(),
                array_slice(array_reverse($this->getInheritanceClassHierarchy()), 1),
            ),
            true,
        );
    }

    /**
     * Checks whether this class implements the given interface.
     *
     * @link https://php.net/manual/en/reflectionclass.implementsinterface.php
     */
    public function implementsInterface(string $interfaceName): bool
    {
        return in_array(ltrim($interfaceName, '\\'), $this->getInterfaceNames(), true);
    }

    /**
     * Checks whether this reflection is an instantiable class
     *
     * @link https://php.net/manual/en/reflectionclass.isinstantiable.php
     */
    public function isInstantiable(): bool
    {
        // @TODO doesn't consider internal non-instantiable classes yet.

        if ($this->isAbstract()) {
            return false;
        }

        if ($this->isInterface()) {
            return false;
        }

        if ($this->isTrait()) {
            return false;
        }

        $constructor = $this->getConstructor();

        if ($constructor === null) {
            return true;
        }

        return $constructor->isPublic();
    }

    /**
     * Checks whether this is a reflection of a class that supports the clone operator
     *
     * @link https://php.net/manual/en/reflectionclass.iscloneable.php
     */
    public function isCloneable(): bool
    {
        if (! $this->isInstantiable()) {
            return false;
        }

        $cloneMethod = $this->getMethod('__clone');

        if ($cloneMethod === null) {
            return true;
        }

        return $cloneMethod->isPublic();
    }

    /**
     * Checks if iterateable
     *
     * @link https://php.net/manual/en/reflectionclass.isiterateable.php
     */
    public function isIterateable(): bool
    {
        return $this->isInstantiable() && $this->implementsInterface(Traversable::class);
    }

    public function isEnum(): bool
    {
        return $this->isEnum;
    }

    /** @return array<class-string, ReflectionClass> */
    private function getCurrentClassImplementedInterfacesIndexedByName(): array
    {
        if ($this->isTrait) {
            return [];
        }

        if ($this->isInterface) {
            // assumption: first key is the current interface
            return array_slice($this->getInterfacesHierarchy(), 1);
        }

        $interfaces = array_merge(
            [],
            ...array_map(
                fn (string $interfaceClassName): array => $this->reflector
                    ->reflectClass($interfaceClassName)
                    ->getInterfacesHierarchy(),
                $this->implementsClassNames,
            ),
        );

        if ($this->isEnum) {
            $interfaces = $this->addEnumInterfaces($interfaces);
        }

        return $this->addStringableInterface($interfaces);
    }

    /** @return list<ReflectionClass> ordered from inheritance root to leaf (this class) */
    private function getInheritanceClassHierarchy(): array
    {
        $parentClass = $this->getParentClass();

        return $parentClass
            ? array_merge($parentClass->getInheritanceClassHierarchy(), [$this])
            : [$this];
    }

    /**
     * This method allows us to retrieve all interfaces parent of the this interface. Do not use on class nodes!
     *
     * @return array<class-string, ReflectionClass> parent interfaces of this interface
     *
     * @throws NotAnInterfaceReflection
     */
    private function getInterfacesHierarchy(): array
    {
        if (! $this->isInterface) {
            throw NotAnInterfaceReflection::fromReflectionClass($this);
        }

        /** @var array<class-string, self> $interfaces */
        $interfaces = array_merge(
            [$this->getName() => $this],
            ...array_map(
                fn (string $interfaceClassName): array => $this->reflector
                    ->reflectClass($interfaceClassName)
                    ->getInterfacesHierarchy(),
                $this->implementsClassNames,
            ),
        );

        return $this->addStringableInterface($interfaces);
    }

    /**
     * Get the value of a static property, if it exists. Throws a
     * PropertyDoesNotExist exception if it does not exist or is not static.
     * (note, differs very slightly from internal reflection behaviour)
     *
     * @param non-empty-string $propertyName
     *
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     */
    public function getStaticPropertyValue(string $propertyName): mixed
    {
        $property = $this->getProperty($propertyName);

        if (! $property || ! $property->isStatic()) {
            throw PropertyDoesNotExist::fromName($propertyName);
        }

        return $property->getValue();
    }

    /**
     * Set the value of a static property
     *
     * @param non-empty-string $propertyName
     *
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     */
    public function setStaticPropertyValue(string $propertyName, mixed $value): void
    {
        $property = $this->getProperty($propertyName);

        if (! $property || ! $property->isStatic()) {
            throw PropertyDoesNotExist::fromName($propertyName);
        }

        $property->setValue($value);
    }

    /** @return array<non-empty-string, mixed> */
    public function getStaticProperties(): array
    {
        $staticProperties = [];

        foreach ($this->getProperties() as $property) {
            if (! $property->isStatic()) {
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $staticProperties[$property->getName()] = $property->getValue();
        }

        return $staticProperties;
    }

    /** @return list<ReflectionAttribute> */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /** @return list<ReflectionAttribute> */
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
