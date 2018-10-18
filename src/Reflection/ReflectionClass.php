<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use InvalidArgumentException;
use OutOfBoundsException;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\ClassConst as ConstNode;
use PhpParser\Node\Stmt\ClassLike as ClassLikeNode;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_ as InterfaceNode;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\Node\Stmt\Trait_ as TraitNode;
use PhpParser\Node\Stmt\TraitUse;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use ReflectionProperty as CoreReflectionProperty;
use Reflector as CoreReflector;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAClassReflection;
use Roave\BetterReflection\Reflection\Exception\NotAnInterfaceReflection;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\Exception\PropertyDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\StringCast\ReflectionClassStringCast;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Util\CalculateReflectionColum;
use Roave\BetterReflection\Util\GetFirstDocComment;
use Traversable;
use function array_combine;
use function array_filter;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_slice;
use function array_values;
use function implode;
use function in_array;
use function is_object;
use function ltrim;
use function sha1;
use function sprintf;
use function strtolower;

class ReflectionClass implements Reflection, CoreReflector
{
    public const ANONYMOUS_CLASS_NAME_PREFIX = 'class@anonymous';

    /** @var Reflector */
    private $reflector;

    /** @var NamespaceNode|null */
    private $declaringNamespace;

    /** @var LocatedSource */
    private $locatedSource;

    /** @var ClassLikeNode */
    private $node;

    /** @var ReflectionClassConstant[]|null indexed by name, when present */
    private $cachedReflectionConstants;

    /** @var ReflectionProperty[]|null */
    private $cachedImmediateProperties;

    /** @var ReflectionProperty[]|null */
    private $cachedProperties;

    /** @var ReflectionMethod[]|null */
    private $cachedMethods;

    private function __construct()
    {
    }

    /**
     * Create a reflection and return the string representation of a named class
     */
    public static function export(?string $className) : string
    {
        if ($className === null) {
            throw new InvalidArgumentException('Class name must be provided');
        }

        return self::createFromName($className)->__toString();
    }

    public function __toString() : string
    {
        return ReflectionClassStringCast::toString($this);
    }

    /**
     * Create a ReflectionClass by name, using default reflectors etc.
     *
     * @throws IdentifierNotFound
     */
    public static function createFromName(string $className) : self
    {
        return (new BetterReflection())->classReflector()->reflect($className);
    }

    /**
     * Create a ReflectionClass from an instance, using default reflectors etc.
     *
     * This is simply a helper method that calls ReflectionObject::createFromInstance().
     *
     * @see ReflectionObject::createFromInstance
     *
     * @param object $instance
     *
     * @throws IdentifierNotFound
     * @throws ReflectionException
     * @throws InvalidArgumentException
     *
     * @psalm-suppress DocblockTypeContradiction
     */
    public static function createFromInstance($instance) : self
    {
        if (! is_object($instance)) {
            throw new InvalidArgumentException('Instance must be an instance of an object');
        }

        return ReflectionObject::createFromInstance($instance);
    }

    /**
     * Create from a Class Node.
     *
     * @internal
     *
     * @param ClassLikeNode      $node      Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     * @param NamespaceNode|null $namespace optional - if omitted, we assume it is global namespaced class
     */
    public static function createFromNode(
        Reflector $reflector,
        ClassLikeNode $node,
        LocatedSource $locatedSource,
        ?NamespaceNode $namespace = null
    ) : self {
        $class = new self();

        $class->reflector     = $reflector;
        $class->locatedSource = $locatedSource;
        $class->node          = $node;

        if ($namespace !== null) {
            $class->declaringNamespace = $namespace;
        }

        return $class;
    }

    /**
     * Get the "short" name of the class (e.g. for A\B\Foo, this will return
     * "Foo").
     */
    public function getShortName() : string
    {
        if (! $this->isAnonymous()) {
            /** @var Node\Identifier $this->node->name */
            return $this->node->name->name;
        }

        $fileName = $this->getFileName();

        if ($fileName === null) {
            $fileName = sha1($this->locatedSource->getSource());
        }

        return sprintf('%s%c%s(%d)', self::ANONYMOUS_CLASS_NAME_PREFIX, "\0", $fileName, $this->getStartLine());
    }

    /**
     * Get the "full" name of the class (e.g. for A\B\Foo, this will return
     * "A\B\Foo").
     */
    public function getName() : string
    {
        if (! $this->inNamespace()) {
            return $this->getShortName();
        }

        return $this->node->namespacedName->toString();
    }

    /**
     * Get the "namespace" name of the class (e.g. for A\B\Foo, this will
     * return "A\B").
     *
     * @psalm-suppress PossiblyNullPropertyFetch
     */
    public function getNamespaceName() : string
    {
        if (! $this->inNamespace()) {
            return '';
        }

        return implode('\\', $this->declaringNamespace->name->parts);
    }

    /**
     * Decide if this class is part of a namespace. Returns false if the class
     * is in the global namespace or does not have a specified namespace.
     */
    public function inNamespace() : bool
    {
        return $this->declaringNamespace !== null
            && $this->declaringNamespace->name !== null;
    }

    public function getExtensionName() : ?string
    {
        return $this->locatedSource->getExtensionName();
    }

    /**
     * Construct a flat list of all methods from current class, traits,
     * parent classes and interfaces in this precise order.
     *
     * @return ReflectionMethod[]
     */
    private function getAllMethods() : array
    {
        return array_merge(
            [],
            array_map(
                function (ClassMethod $methodNode) : ReflectionMethod {
                    return ReflectionMethod::createFromNode(
                        $this->reflector,
                        $methodNode,
                        $this->declaringNamespace,
                        $this,
                        $this
                    );
                },
                $this->node->getMethods()
            ),
            ...array_map(
                function (ReflectionClass $trait) : array {
                    return array_map(function (ReflectionMethod $method) use ($trait) : ReflectionMethod {
                        /** @var ClassMethod $methodAst */
                        $methodAst = $method->getAst();

                        return ReflectionMethod::createFromNode(
                            $this->reflector,
                            $methodAst,
                            $this->declaringNamespace,
                            $trait,
                            $this
                        );
                    }, $trait->getMethods());
                },
                $this->getTraits()
            ),
            ...array_map(
                static function (ReflectionClass $ancestor) : array {
                    return $ancestor->getMethods();
                },
                array_values(array_merge(
                    array_filter([$this->getParentClass()]),
                    $this->getInterfaces()
                ))
            )
        );
    }

    /**
     * Construct a flat list of methods that are available. This will search up
     * all parent classes/traits/interfaces/current scope for methods.
     *
     * Methods are not merged via their name as array index, since internal PHP method
     * sorting does not follow `\array_merge()` semantics.
     *
     * @return ReflectionMethod[] indexed by method name
     */
    private function getMethodsIndexedByName() : array
    {
        if ($this->cachedMethods !== null) {
            return $this->cachedMethods;
        }

        $this->cachedMethods = [];

        $traitAliases = $this->getTraitAliases();

        foreach ($this->getAllMethods() as $method) {
            $methodName              = $method->getName();
            $methodNameWithClassName = sprintf('%s::%s', $method->getDeclaringClass()->getName(), $methodName);

            foreach ($traitAliases as $methodAlias => $traitMethodNameWithTraitName) {
                if ($methodNameWithClassName !== $traitMethodNameWithTraitName) {
                    continue;
                }

                if (isset($this->cachedMethods[$methodAlias])) {
                    continue;
                }

                $this->cachedMethods[$methodAlias] = $method;
            }

            if (isset($this->cachedMethods[$methodName])) {
                continue;
            }

            $this->cachedMethods[$methodName] = $method;
        }

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
     * @return ReflectionMethod[]
     */
    public function getMethods(?int $filter = null) : array
    {
        if ($filter === null) {
            return array_values($this->getMethodsIndexedByName());
        }

        return array_values(
            array_filter(
                $this->getMethodsIndexedByName(),
                static function (ReflectionMethod $method) use ($filter) : bool {
                    return (bool) ($filter & $method->getModifiers());
                }
            )
        );
    }

    /**
     * Get only the methods that this class implements (i.e. do not search
     * up parent classes etc.)
     *
     * @see ReflectionClass::getMethods for the usage of $filter
     *
     * @return ReflectionMethod[]
     */
    public function getImmediateMethods(?int $filter = null) : array
    {
        /** @var ReflectionMethod[] $methods */
        $methods = array_map(
            function (ClassMethod $methodNode) : ReflectionMethod {
                return ReflectionMethod::createFromNode(
                    $this->reflector,
                    $methodNode,
                    $this->declaringNamespace,
                    $this,
                    $this
                );
            },
            $this->node->getMethods()
        );

        $methodsByName = [];

        foreach ($methods as $method) {
            if ($filter !== null && ! ($filter & $method->getModifiers())) {
                continue;
            }

            $methodsByName[$method->getName()] = $method;
        }

        return $methodsByName;
    }

    /**
     * Get a single method with the name $methodName.
     *
     * @throws OutOfBoundsException
     */
    public function getMethod(string $methodName) : ReflectionMethod
    {
        $methods = $this->getMethodsIndexedByName();

        if (! isset($methods[$methodName])) {
            throw new OutOfBoundsException('Could not find method: ' . $methodName);
        }

        return $methods[$methodName];
    }

    /**
     * Does the class have the specified method method?
     */
    public function hasMethod(string $methodName) : bool
    {
        try {
            $this->getMethod($methodName);
            return true;
        } catch (OutOfBoundsException $exception) {
            return false;
        }
    }

    /**
     * Get an associative array of only the constants for this specific class (i.e. do not search
     * up parent classes etc.), with keys as constant names and values as constant values.
     *
     * @return string[]|int[]|float[]|array[]|bool[]|null[]
     */
    public function getImmediateConstants() : array
    {
        return array_map(static function (ReflectionClassConstant $classConstant) {
            return $classConstant->getValue();
        }, $this->getImmediateReflectionConstants());
    }

    /**
     * Get an associative array of the defined constants in this class,
     * with keys as constant names and values as constant values.
     *
     * @return string[]|int[]|float[]|array[]|bool[]|null[]
     */
    public function getConstants() : array
    {
        return array_map(static function (ReflectionClassConstant $classConstant) {
            return $classConstant->getValue();
        }, $this->getReflectionConstants());
    }

    /**
     * Get the value of the specified class constant.
     *
     * Returns null if not specified.
     *
     * @return mixed|null
     */
    public function getConstant(string $name)
    {
        $reflectionConstant = $this->getReflectionConstant($name);

        if (! $reflectionConstant) {
            return null;
        }

        return $reflectionConstant->getValue();
    }

    /**
     * Does this class have the specified constant?
     */
    public function hasConstant(string $name) : bool
    {
        return $this->getReflectionConstant($name) !== null;
    }

    /**
     * Get the reflection object of the specified class constant.
     *
     * Returns null if not specified.
     */
    public function getReflectionConstant(string $name) : ?ReflectionClassConstant
    {
        return $this->getReflectionConstants()[$name] ?? null;
    }

    /**
     * Get an associative array of only the constants for this specific class (i.e. do not search
     * up parent classes etc.), with keys as constant names and values as {@see ReflectionClassConstant} objects.
     *
     * @return ReflectionClassConstant[] indexed by name
     */
    public function getImmediateReflectionConstants() : array
    {
        if ($this->cachedReflectionConstants !== null) {
            return $this->cachedReflectionConstants;
        }

        $constants = array_merge(
            [],
            ...array_map(
                function (ConstNode $constNode) : array {
                    $constants = [];

                    foreach ($constNode->consts as $constantPositionInNode => $constantNode) {
                        $constants[] = ReflectionClassConstant::createFromNode($this->reflector, $constNode, $constantPositionInNode, $this);
                    }

                    return $constants;
                },
                array_filter(
                    $this->node->stmts,
                    static function (Node\Stmt $stmt) : bool {
                        return $stmt instanceof ConstNode;
                    }
                )
            )
        );

        return $this->cachedReflectionConstants = array_combine(
            array_map(
                static function (ReflectionClassConstant $constant) : string {
                    return $constant->getName();
                },
                $constants
            ),
            $constants
        );
    }

    /**
     * Get an associative array of the defined constants in this class,
     * with keys as constant names and values as {@see ReflectionClassConstant} objects.
     *
     * @return ReflectionClassConstant[] indexed by name
     */
    public function getReflectionConstants() : array
    {
        // Note: constants are not merged via their name as array index, since internal PHP constant
        //       sorting does not follow `\array_merge()` semantics
        /** @var ReflectionClassConstant[] $allReflectionConstants */
        $allReflectionConstants = array_merge(
            array_values($this->getImmediateReflectionConstants()),
            ...array_map(
                static function (ReflectionClass $ancestor) : array {
                    return array_filter(
                        array_values($ancestor->getReflectionConstants()),
                        static function (ReflectionClassConstant $classConstant) : bool {
                            return ! $classConstant->isPrivate();
                        }
                    );
                },
                array_filter([$this->getParentClass()])
            ),
            ...array_map(
                static function (ReflectionClass $interface) : array {
                    return array_values($interface->getReflectionConstants());
                },
                array_values($this->getInterfaces())
            )
        );

        $reflectionConstants = [];

        foreach ($allReflectionConstants as $constant) {
            $constantName = $constant->getName();

            if (isset($reflectionConstants[$constantName])) {
                continue;
            }

            $reflectionConstants[$constantName] = $constant;
        }

        return $reflectionConstants;
    }

    /**
     * Get the constructor method for this class.
     *
     * @throws OutOfBoundsException
     */
    public function getConstructor() : ReflectionMethod
    {
        $constructors = array_filter($this->getMethods(), static function (ReflectionMethod $method) : bool {
            return $method->isConstructor();
        });

        if (! isset($constructors[0])) {
            throw new OutOfBoundsException('Could not find method: __construct');
        }

        return $constructors[0];
    }

    /**
     * Get only the properties for this specific class (i.e. do not search
     * up parent classes etc.)
     *
     * @see ReflectionClass::getProperties() for the usage of filter
     *
     * @return ReflectionProperty[]
     */
    public function getImmediateProperties(?int $filter = null) : array
    {
        if ($this->cachedImmediateProperties === null) {
            $properties = [];
            foreach ($this->node->stmts as $stmt) {
                if (! ($stmt instanceof PropertyNode)) {
                    continue;
                }

                foreach ($stmt->props as $propertyPositionInNode => $propertyNode) {
                    $prop                         = ReflectionProperty::createFromNode(
                        $this->reflector,
                        $stmt,
                        $propertyPositionInNode,
                        $this->declaringNamespace,
                        $this,
                        $this
                    );
                    $properties[$prop->getName()] = $prop;
                }
            }

            $this->cachedImmediateProperties = $properties;
        }

        if ($filter === null) {
            return $this->cachedImmediateProperties;
        }

        return array_filter(
            $this->cachedImmediateProperties,
            static function (ReflectionProperty $property) use ($filter) : bool {
                return (bool) ($filter & $property->getModifiers());
            }
        );
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
     * @return ReflectionProperty[]
     */
    public function getProperties(?int $filter = null) : array
    {
        if ($this->cachedProperties === null) {
            // merging together properties from parent class, traits, current class (in this precise order)
            $this->cachedProperties = array_merge(
                array_merge(
                    [],
                    ...array_map(
                        static function (ReflectionClass $ancestor) use ($filter) : array {
                            return array_filter(
                                $ancestor->getProperties($filter),
                                static function (ReflectionProperty $property) : bool {
                                    return ! $property->isPrivate();
                                }
                            );
                        },
                        array_filter([$this->getParentClass()])
                    ),
                    ...array_map(
                        function (ReflectionClass $trait) use ($filter) {
                            return array_map(function (ReflectionProperty $property) use ($trait) : ReflectionProperty {
                                return ReflectionProperty::createFromNode(
                                    $this->reflector,
                                    $property->getAst(),
                                    $property->getPositionInAst(),
                                    $trait->declaringNamespace,
                                    $property->getDeclaringClass(),
                                    $this
                                );
                            }, $trait->getProperties($filter));
                        },
                        $this->getTraits()
                    )
                ),
                $this->getImmediateProperties()
            );
        }

        if ($filter === null) {
            return $this->cachedProperties;
        }

        return array_filter(
            $this->cachedProperties,
            static function (ReflectionProperty $property) use ($filter) : bool {
                return (bool) ($filter & $property->getModifiers());
            }
        );
    }

    /**
     * Get the property called $name.
     *
     * Returns null if property does not exist.
     */
    public function getProperty(string $name) : ?ReflectionProperty
    {
        $properties = $this->getProperties();

        if (! isset($properties[$name])) {
            return null;
        }

        return $properties[$name];
    }

    /**
     * Does this class have the specified property?
     */
    public function hasProperty(string $name) : bool
    {
        return $this->getProperty($name) !== null;
    }

    /**
     * @return mixed[]
     */
    public function getDefaultProperties() : array
    {
        return array_map(
            static function (ReflectionProperty $property) {
                return $property->getDefaultValue();
            },
            array_filter($this->getProperties(), static function (ReflectionProperty $property) : bool {
                return $property->isDefault();
            })
        );
    }

    public function getFileName() : ?string
    {
        return $this->locatedSource->getFileName();
    }

    public function getLocatedSource() : LocatedSource
    {
        return $this->locatedSource;
    }

    /**
     * Get the line number that this class starts on.
     */
    public function getStartLine() : int
    {
        return $this->node->getStartLine();
    }

    /**
     * Get the line number that this class ends on.
     */
    public function getEndLine() : int
    {
        return $this->node->getEndLine();
    }

    public function getStartColumn() : int
    {
        return CalculateReflectionColum::getStartColumn($this->locatedSource->getSource(), $this->node);
    }

    public function getEndColumn() : int
    {
        return CalculateReflectionColum::getEndColumn($this->locatedSource->getSource(), $this->node);
    }

    /**
     * Get the parent class, if it is defined. If this class does not have a
     * specified parent class, this will throw an exception.
     *
     * @throws NotAClassReflection
     */
    public function getParentClass() : ?ReflectionClass
    {
        if (! ($this->node instanceof ClassNode) || $this->node->extends === null) {
            return null;
        }

        // @TODO use actual `ClassReflector` or `FunctionReflector`?
        /** @var self $parent */
        $parent = $this->reflector->reflect($this->node->extends->toString());

        if ($parent->isInterface() || $parent->isTrait()) {
            throw NotAClassReflection::fromReflectionClass($parent);
        }

        return $parent;
    }

    /**
     * Gets the parent class names.
     *
     * @return string[] A numerical array with parent class names as the values.
     */
    public function getParentClassNames() : array
    {
        return array_map(static function (self $parentClass) : string {
            return $parentClass->getName();
        }, array_slice(array_reverse($this->getInheritanceClassHierarchy()), 1));
    }

    public function getDocComment() : string
    {
        return GetFirstDocComment::forNode($this->node);
    }

    public function isAnonymous() : bool
    {
        return $this->node->name === null;
    }

    /**
     * Is this an internal class?
     */
    public function isInternal() : bool
    {
        return $this->locatedSource->isInternal();
    }

    /**
     * Is this a user-defined function (will always return the opposite of
     * whatever isInternal returns).
     */
    public function isUserDefined() : bool
    {
        return ! $this->isInternal();
    }

    /**
     * Is this class an abstract class.
     */
    public function isAbstract() : bool
    {
        return $this->node instanceof ClassNode && $this->node->isAbstract();
    }

    /**
     * Is this class a final class.
     */
    public function isFinal() : bool
    {
        return $this->node instanceof ClassNode && $this->node->isFinal();
    }

    /**
     * Get the core-reflection-compatible modifier values.
     */
    public function getModifiers() : int
    {
        $val  = 0;
        $val += $this->isAbstract() ? CoreReflectionClass::IS_EXPLICIT_ABSTRACT : 0;
        $val += $this->isFinal() ? CoreReflectionClass::IS_FINAL : 0;
        return $val;
    }

    /**
     * Is this reflection a trait?
     */
    public function isTrait() : bool
    {
        return $this->node instanceof TraitNode;
    }

    /**
     * Is this reflection an interface?
     */
    public function isInterface() : bool
    {
        return $this->node instanceof InterfaceNode;
    }

    /**
     * Get the traits used, if any are defined. If this class does not have any
     * defined traits, this will return an empty array.
     *
     * @return ReflectionClass[]
     */
    public function getTraits() : array
    {
        return array_map(
            function (Node\Name $importedTrait) : ReflectionClass {
                return $this->reflectClassForNamedNode($importedTrait);
            },
            array_merge(
                [],
                ...array_map(
                    static function (TraitUse $traitUse) : array {
                        return $traitUse->traits;
                    },
                    array_filter($this->node->stmts, static function (Node $node) : bool {
                        return $node instanceof TraitUse;
                    })
                )
            )
        );
    }

    /**
     * Given an AST Node\Name, create a new ReflectionClass for the element.
     */
    private function reflectClassForNamedNode(Node\Name $node) : self
    {
        // @TODO use actual `ClassReflector` or `FunctionReflector`?
        if ($this->isAnonymous()) {
            /** @var self $class */
            $class = (new BetterReflection())->classReflector()->reflect($node->toString());
        } else {
            /** @var self $class */
            $class = $this->reflector->reflect($node->toString());
        }

        return $class;
    }

    /**
     * Get the names of the traits used as an array of strings, if any are
     * defined. If this class does not have any defined traits, this will
     * return an empty array.
     *
     * @return string[]
     */
    public function getTraitNames() : array
    {
        return array_map(
            static function (ReflectionClass $trait) : string {
                return $trait->getName();
            },
            $this->getTraits()
        );
    }

    /**
     * Return a list of the aliases used when importing traits for this class.
     * The returned array is in key/value pair in this format:.
     *
     *   'aliasedMethodName' => 'ActualClass::actualMethod'
     *
     * @return string[]
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
    public function getTraitAliases() : array
    {
        /** @var Node\Stmt\TraitUse[] $traitUsages */
        $traitUsages = array_filter($this->node->stmts, static function (Node $node) : bool {
            return $node instanceof TraitUse;
        });

        $resolvedAliases = [];

        foreach ($traitUsages as $traitUsage) {
            $traitNames  = $traitUsage->traits;
            $adaptations = $traitUsage->adaptations;

            foreach ($adaptations as $adaptation) {
                /** @var Node\Name|null $usedTrait */
                $usedTrait = $adaptation->trait;
                if ($usedTrait === null) {
                    $usedTrait = $traitNames[0];
                }

                if (! ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Alias)) {
                    continue;
                }

                if (! $adaptation->newName) {
                    continue;
                }

                $resolvedAliases[$adaptation->newName->name] = sprintf(
                    '%s::%s',
                    $usedTrait->toString(),
                    $adaptation->method->toString()
                );
            }
        }

        return $resolvedAliases;
    }

    /**
     * Gets the interfaces.
     *
     * @link https://php.net/manual/en/reflectionclass.getinterfaces.php
     *
     * @return ReflectionClass[] An associative array of interfaces, with keys as interface names and the array
     *                           values as {@see ReflectionClass} objects.
     */
    public function getInterfaces() : array
    {
        return array_merge(...array_map(
            static function (self $reflectionClass) : array {
                return $reflectionClass->getCurrentClassImplementedInterfacesIndexedByName();
            },
            $this->getInheritanceClassHierarchy()
        ));
    }

    /**
     * Get only the interfaces that this class implements (i.e. do not search
     * up parent classes etc.)
     *
     * @return ReflectionClass[]
     */
    public function getImmediateInterfaces() : array
    {
        return $this->getCurrentClassImplementedInterfacesIndexedByName();
    }

    /**
     * Gets the interface names.
     *
     * @link https://php.net/manual/en/reflectionclass.getinterfacenames.php
     *
     * @return string[] A numerical array with interface names as the values.
     */
    public function getInterfaceNames() : array
    {
        return array_values(array_map(
            static function (self $interface) : string {
                return $interface->getName();
            },
            $this->getInterfaces()
        ));
    }

    /**
     * Checks whether the given object is an instance.
     *
     * @link https://php.net/manual/en/reflectionclass.isinstance.php
     *
     * @param object $object
     *
     * @throws NotAnObject
     *
     * @psalm-suppress DocblockTypeContradiction
     */
    public function isInstance($object) : bool
    {
        if (! is_object($object)) {
            throw NotAnObject::fromNonObject($object);
        }

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
    public function isSubclassOf(string $className) : bool
    {
        return in_array(
            ltrim($className, '\\'),
            array_map(
                static function (self $reflectionClass) : string {
                    return $reflectionClass->getName();
                },
                array_slice(array_reverse($this->getInheritanceClassHierarchy()), 1)
            ),
            true
        );
    }

    /**
     * Checks whether this class implements the given interface.
     *
     * @link https://php.net/manual/en/reflectionclass.implementsinterface.php
     */
    public function implementsInterface(string $interfaceName) : bool
    {
        return in_array(ltrim($interfaceName, '\\'), $this->getInterfaceNames(), true);
    }

    /**
     * Checks whether this reflection is an instantiable class
     *
     * @link https://php.net/manual/en/reflectionclass.isinstantiable.php
     */
    public function isInstantiable() : bool
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

        try {
            return $this->getConstructor()->isPublic();
        } catch (OutOfBoundsException $e) {
            return true;
        }
    }

    /**
     * Checks whether this is a reflection of a class that supports the clone operator
     *
     * @link https://php.net/manual/en/reflectionclass.iscloneable.php
     */
    public function isCloneable() : bool
    {
        if (! $this->isInstantiable()) {
            return false;
        }

        if (! $this->hasMethod('__clone')) {
            return true;
        }

        return $this->getMethod('__clone')->isPublic();
    }

    /**
     * Checks if iterateable
     *
     * @link https://php.net/manual/en/reflectionclass.isiterateable.php
     */
    public function isIterateable() : bool
    {
        return $this->isInstantiable() && $this->implementsInterface(Traversable::class);
    }

    /**
     * @return ReflectionClass[] indexed by interface name
     */
    private function getCurrentClassImplementedInterfacesIndexedByName() : array
    {
        $node = $this->node;

        if ($node instanceof ClassNode) {
            return array_merge(
                [],
                ...array_map(
                    function (Node\Name $interfaceName) : array {
                        return $this
                            ->reflectClassForNamedNode($interfaceName)
                            ->getInterfacesHierarchy();
                    },
                    $node->implements
                )
            );
        }

        // assumption: first key is the current interface
        return $this->isInterface()
            ? array_slice($this->getInterfacesHierarchy(), 1)
            : [];
    }

    /**
     * @return ReflectionClass[] ordered from inheritance root to leaf (this class)
     */
    private function getInheritanceClassHierarchy() : array
    {
        $parentClass = $this->getParentClass();

        return $parentClass
            ? array_merge($parentClass->getInheritanceClassHierarchy(), [$this])
            : [$this];
    }

    /**
     * This method allows us to retrieve all interfaces parent of the this interface. Do not use on class nodes!
     *
     * @return ReflectionClass[] parent interfaces of this interface
     *
     * @throws NotAnInterfaceReflection
     */
    private function getInterfacesHierarchy() : array
    {
        if (! $this->isInterface()) {
            throw NotAnInterfaceReflection::fromReflectionClass($this);
        }

        /** @var InterfaceNode $node */
        $node = $this->node;

        return array_merge(
            [$this->getName() => $this],
            ...array_map(
                function (Node\Name $interfaceName) : array {
                    return $this
                        ->reflectClassForNamedNode($interfaceName)
                        ->getInterfacesHierarchy();
                },
                $node->extends
            )
        );
    }

    /**
     * @throws Uncloneable
     */
    public function __clone()
    {
        throw Uncloneable::fromClass(static::class);
    }

    /**
     * Get the value of a static property, if it exists. Throws a
     * PropertyDoesNotExist exception if it does not exist or is not static.
     * (note, differs very slightly from internal reflection behaviour)
     *
     * @return mixed
     *
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     */
    public function getStaticPropertyValue(string $propertyName)
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
     * @param mixed $value
     *
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     */
    public function setStaticPropertyValue(string $propertyName, $value) : void
    {
        $property = $this->getProperty($propertyName);

        if (! $property || ! $property->isStatic()) {
            throw PropertyDoesNotExist::fromName($propertyName);
        }

        $property->setValue($value);
    }

    /**
     * @return mixed[]
     */
    public function getStaticProperties() : array
    {
        $staticProperties = [];

        foreach ($this->getProperties() as $property) {
            if (! $property->isStatic()) {
                continue;
            }

            $staticProperties[$property->getName()] = $property->getValue();
        }

        return $staticProperties;
    }

    /**
     * Retrieve the AST node for this class
     */
    public function getAst() : ClassLikeNode
    {
        return $this->node;
    }

    public function getDeclaringNamespaceAst() : ?Namespace_
    {
        return $this->declaringNamespace;
    }

    /**
     * Set whether this class is final or not
     *
     * @throws NotAClassReflection
     */
    public function setFinal(bool $isFinal) : void
    {
        if (! $this->node instanceof ClassNode) {
            throw NotAClassReflection::fromReflectionClass($this);
        }

        if ($isFinal === true) {
            $this->node->flags |= ClassNode::MODIFIER_FINAL;
            return;
        }

        $this->node->flags &= ~ClassNode::MODIFIER_FINAL;
    }

    /**
     * Remove the named method from the class.
     *
     * Returns true if method was successfully removed.
     * Returns false if method was not found, or could not be removed.
     */
    public function removeMethod(string $methodName) : bool
    {
        $lowerName = strtolower($methodName);
        foreach ($this->node->stmts as $key => $stmt) {
            if ($stmt instanceof ClassMethod && $lowerName === $stmt->name->toLowerString()) {
                unset($this->node->stmts[$key], $this->cachedMethods);
                return true;
            }
        }
        return false;
    }

    /**
     * Add a new method to the class.
     */
    public function addMethod(string $methodName) : void
    {
        $this->node->stmts[] = new ClassMethod($methodName);
        unset($this->cachedMethods);
    }

    /**
     * Add a new property to the class.
     *
     * Visibility defaults to \ReflectionProperty::IS_PUBLIC, or can be ::IS_PROTECTED or ::IS_PRIVATE.
     */
    public function addProperty(
        string $propertyName,
        int $visibility = CoreReflectionProperty::IS_PUBLIC,
        bool $static = false
    ) : void {
        $type = 0;
        switch ($visibility) {
            case CoreReflectionProperty::IS_PRIVATE:
                $type |= ClassNode::MODIFIER_PRIVATE;
                break;
            case CoreReflectionProperty::IS_PROTECTED:
                $type |= ClassNode::MODIFIER_PROTECTED;
                break;
            default:
                $type |= ClassNode::MODIFIER_PUBLIC;
                break;
        }

        if ($static) {
            $type |= ClassNode::MODIFIER_STATIC;
        }

        $this->node->stmts[]             = new PropertyNode($type, [new Node\Stmt\PropertyProperty($propertyName)]);
        $this->cachedProperties          = null;
        $this->cachedImmediateProperties = null;
    }

    /**
     * Remove a property from the class.
     */
    public function removeProperty(string $propertyName) : bool
    {
        $lowerName = strtolower($propertyName);

        foreach ($this->node->stmts as $key => $stmt) {
            if (! ($stmt instanceof PropertyNode)) {
                continue;
            }

            $propertyNames = array_map(static function (Node\Stmt\PropertyProperty $propertyProperty) : string {
                return $propertyProperty->name->toLowerString();
            }, $stmt->props);

            if (in_array($lowerName, $propertyNames, true)) {
                $this->cachedProperties          = null;
                $this->cachedImmediateProperties = null;
                unset($this->node->stmts[$key]);

                return true;
            }
        }

        return false;
    }
}
