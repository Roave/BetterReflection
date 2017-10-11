<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflection;

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
use ReflectionProperty as CoreReflectionProperty;
use Reflector as CoreReflector;
use Rector\BetterReflection\BetterReflection;
use Rector\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Rector\BetterReflection\Reflection\Exception\NoObjectProvided;
use Rector\BetterReflection\Reflection\Exception\NotAClassReflection;
use Rector\BetterReflection\Reflection\Exception\NotAnInterfaceReflection;
use Rector\BetterReflection\Reflection\Exception\NotAnObject;
use Rector\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Rector\BetterReflection\Reflection\Exception\PropertyDoesNotExist;
use Rector\BetterReflection\Reflection\Exception\Uncloneable;
use Rector\BetterReflection\Reflection\StringCast\ReflectionClassStringCast;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;
use Rector\BetterReflection\Util\CalculateReflectionColum;
use Rector\BetterReflection\Util\GetFirstDocComment;
use Traversable;

class ReflectionClass implements Reflection, CoreReflector
{
    public const ANONYMOUS_CLASS_NAME_PREFIX = 'class@anonymous';

    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var NamespaceNode|null
     */
    private $declaringNamespace;

    /**
     * @var LocatedSource
     */
    private $locatedSource;

    /**
     * @var ClassLikeNode
     */
    private $node;

    /**
     * @var ReflectionClassConstant[]|null indexed by name, when present
     */
    private $cachedReflectionConstants;

    /**
     * @var ReflectionProperty[]|null
     */
    private $cachedImmediateProperties;

    /**
     * @var ReflectionProperty[]|null
     */
    private $cachedProperties;

    /**
     * @var ReflectionMethod[]|null
     */
    private $cachedMethods;

    private function __construct()
    {
    }

    /**
     * Create a reflection and return the string representation of a named class
     *
     * @param string $className
     * @return string
     */
    public static function export(?string $className) : string
    {
        if (null === $className) {
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
     * @throws \Rector\BetterReflection\Reflector\Exception\IdentifierNotFound
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
     * @return ReflectionClass
     * @throws \Rector\BetterReflection\Reflector\Exception\IdentifierNotFound
     * @throws \ReflectionException
     * @throws \InvalidArgumentException
     */
    public static function createFromInstance($instance) : self
    {
        if ( ! \is_object($instance)) {
            throw new InvalidArgumentException('Instance must be an instance of an object');
        }

        return ReflectionObject::createFromInstance($instance);
    }

    /**
     * Create from a Class Node.
     *
     * @internal
     * @param Reflector          $reflector
     * @param ClassLikeNode      $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     * @param LocatedSource      $locatedSource
     * @param NamespaceNode|null $namespace optional - if omitted, we assume it is global namespaced class
     *
     * @return ReflectionClass
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

        if (null !== $namespace) {
            $class->declaringNamespace = $namespace;
        }

        return $class;
    }

    /**
     * Get the "short" name of the class (e.g. for A\B\Foo, this will return
     * "Foo").
     *
     * @return string
     */
    public function getShortName() : string
    {
        if ( ! $this->isAnonymous()) {
            return (string) $this->node->name;
        }

        $fileName = $this->getFileName();

        if (null === $fileName) {
            $fileName = \sha1($this->locatedSource->getSource());
        }

        return \sprintf('%s%c%s(%d)', self::ANONYMOUS_CLASS_NAME_PREFIX, "\0", $fileName, $this->getStartLine());
    }

    /**
     * Get the "full" name of the class (e.g. for A\B\Foo, this will return
     * "A\B\Foo").
     *
     * @return string
     */
    public function getName() : string
    {
        if ( ! $this->inNamespace()) {
            return $this->getShortName();
        }

        return $this->node->namespacedName->toString();
    }

    /**
     * Get the "namespace" name of the class (e.g. for A\B\Foo, this will
     * return "A\B").
     *
     * @return string
     */
    public function getNamespaceName() : string
    {
        if ( ! $this->inNamespace()) {
            return '';
        }

        return \implode('\\', $this->declaringNamespace->name->parts);
    }

    /**
     * Decide if this class is part of a namespace. Returns false if the class
     * is in the global namespace or does not have a specified namespace.
     *
     * @return bool
     */
    public function inNamespace() : bool
    {
        return null !== $this->declaringNamespace
            && null !== $this->declaringNamespace->name;
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
        return \array_merge(
            [],
            \array_map(
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
            ...\array_map(
                function (ReflectionClass $trait) : array {
                    return \array_map(function (ReflectionMethod $method) use ($trait) : ReflectionMethod {
                        return ReflectionMethod::createFromNode(
                            $this->reflector,
                            $method->getAst(),
                            $this->declaringNamespace,
                            $trait,
                            $this
                        );
                    }, $trait->getMethods());
                },
                $this->getTraits()
            ),
            ...\array_map(
                function (ReflectionClass $ancestor) : array {
                    return $ancestor->getMethods();
                },
                \array_values(\array_merge(
                    \array_filter([$this->getParentClass()]),
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
        if (null !== $this->cachedMethods) {
            return $this->cachedMethods;
        }

        $this->cachedMethods = [];

        foreach ($this->getAllMethods() as $method) {
            $methodName = $method->getName();

            if ( ! isset($this->cachedMethods[$methodName])) {
                $this->cachedMethods[$methodName] = $method;
            }
        }

        return $this->cachedMethods;
    }

    /**
     * Fetch an array of all methods for this class.
     *
     * @param int|null $filter
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
     * @return ReflectionMethod[]
     */
    public function getMethods(?int $filter = null) : array
    {
        if (null === $filter) {
            return \array_values($this->getMethodsIndexedByName());
        }

        return \array_values(
            \array_filter(
                $this->getMethodsIndexedByName(),
                function (ReflectionMethod $method) use ($filter) {
                    return $filter & $method->getModifiers();
                }
            )
        );
    }

    /**
     * Get only the methods that this class implements (i.e. do not search
     * up parent classes etc.)
     *
     * @param int|null $filter
     * @see ReflectionClass::getMethods for the usage of $filter
     * @return ReflectionMethod[]
     */
    public function getImmediateMethods(?int $filter = null) : array
    {
        /** @var \ReflectionMethod[] $methods */
        $methods = \array_map(
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
            if (null === $filter || $filter & $method->getModifiers()) {
                $methodsByName[$method->getName()] = $method;
            }
        }

        return $methodsByName;
    }

    /**
     * Get a single method with the name $methodName.
     *
     * @param string $methodName
     *
     * @return ReflectionMethod
     *
     * @throws \OutOfBoundsException
     */
    public function getMethod(string $methodName) : ReflectionMethod
    {
        $methods = $this->getMethodsIndexedByName();

        if ( ! isset($methods[$methodName])) {
            throw new OutOfBoundsException('Could not find method: ' . $methodName);
        }

        return $methods[$methodName];
    }

    /**
     * Does the class have the specified method method?
     *
     * @param string $methodName
     * @return bool
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
        return \array_map(function (ReflectionClassConstant $classConstant) {
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
        return \array_map(function (ReflectionClassConstant $classConstant) {
            return $classConstant->getValue();
        }, $this->getReflectionConstants());
    }

    /**
     * Get the value of the specified class constant.
     *
     * Returns null if not specified.
     *
     * @param string $name
     * @return mixed|null
     */
    public function getConstant(string $name)
    {
        $reflectionConstant = $this->getReflectionConstant($name);

        if ( ! $reflectionConstant) {
            return null;
        }

        return $reflectionConstant->getValue();
    }

    /**
     * Does this class have the specified constant?
     *
     * @param string $name
     * @return bool
     */
    public function hasConstant(string $name) : bool
    {
        return null !== $this->getReflectionConstant($name);
    }

    /**
     * Get the reflection object of the specified class constant.
     *
     * Returns null if not specified.
     *
     * @param string $name
     * @return ReflectionClassConstant|null
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
        if (null !== $this->cachedReflectionConstants) {
            return $this->cachedReflectionConstants;
        }

        $constants = \array_merge(
            [],
            ...\array_map(
                function (ConstNode $constNode) : array {
                    $constants = [];

                    foreach ($constNode->consts as $constantPositionInNode => $constantNode) {
                        $constants[] = ReflectionClassConstant::createFromNode($this->reflector, $constNode, $constantPositionInNode, $this);
                    }

                    return $constants;
                },
                \array_filter(
                    $this->node->stmts,
                    function (Node\Stmt $stmt) : bool {
                        return $stmt instanceof ConstNode;
                    }
                )
            )
        );

        return $this->cachedReflectionConstants = \array_combine(
            \array_map(
                function (ReflectionClassConstant $constant) : string {
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
        $allReflectionConstants = \array_merge(
            \array_values($this->getImmediateReflectionConstants()),
            ...\array_map(
                function (ReflectionClass $ancestor) : array {
                    return \array_filter(
                        \array_values($ancestor->getReflectionConstants()),
                        function (ReflectionClassConstant $classConstant) : bool {
                            return ! $classConstant->isPrivate();
                        }
                    );
                },
                \array_filter([$this->getParentClass()])
            ),
            ...\array_map(
                function (ReflectionClass $interface) : array {
                    return \array_values($interface->getReflectionConstants());
                },
                \array_values($this->getInterfaces())
            )
        );

        $reflectionConstants = [];

        foreach ($allReflectionConstants as $constant) {
            $constantName = $constant->getName();

            if ( ! isset($reflectionConstants[$constantName])) {
                $reflectionConstants[$constantName] = $constant;
            }
        }

        return $reflectionConstants;
    }

    /**
     * Get the constructor method for this class.
     *
     * @return ReflectionMethod
     * @throws \OutOfBoundsException
     */
    public function getConstructor() : ReflectionMethod
    {
        $constructors = \array_filter($this->getMethods(), function (ReflectionMethod $method) : bool {
            return $method->isConstructor();
        });

        if ( ! isset($constructors[0])) {
            throw new OutOfBoundsException('Could not find method: __construct');
        }

        return $constructors[0];
    }

    /**
     * Get only the properties for this specific class (i.e. do not search
     * up parent classes etc.)
     *
     * @param int|null $filter
     * @see ReflectionClass::getProperties() for the usage of filter
     * @return ReflectionProperty[]
     */
    public function getImmediateProperties(?int $filter = null) : array
    {
        if (null === $this->cachedImmediateProperties) {
            $properties = [];
            foreach ($this->node->stmts as $stmt) {
                if ($stmt instanceof PropertyNode) {
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
            }

            $this->cachedImmediateProperties = $properties;
        }

        if (null === $filter) {
            return $this->cachedImmediateProperties;
        }

        return \array_filter(
            $this->cachedImmediateProperties,
            function (ReflectionProperty $property) use ($filter) {
                return $filter & $property->getModifiers();
            }
        );
    }

    /**
     * Get the properties for this class.
     *
     * @param int|null $filter
     * Filter the results to include only properties with certain attributes. Defaults
     * to no filtering.
     * Any combination of \ReflectionProperty::IS_STATIC,
     * \ReflectionProperty::IS_PUBLIC,
     * \ReflectionProperty::IS_PROTECTED,
     * \ReflectionProperty::IS_PRIVATE.
     * For example if $filter = \ReflectionProperty::IS_STATIC | \ReflectionProperty::IS_PUBLIC
     * only the static public properties will be returned
     * @return ReflectionProperty[]
     */
    public function getProperties(?int $filter = null) : array
    {
        if (null === $this->cachedProperties) {
            // merging together properties from parent class, traits, current class (in this precise order)
            $this->cachedProperties = \array_merge(
                \array_merge(
                    [],
                    ...\array_map(
                        function (ReflectionClass $ancestor) use ($filter) : array {
                            return \array_filter(
                                $ancestor->getProperties($filter),
                                function (ReflectionProperty $property) : bool {
                                    return ! $property->isPrivate();
                                }
                            );
                        },
                        \array_filter([$this->getParentClass()])
                    ),
                    ...\array_map(
                        function (ReflectionClass $trait) use ($filter) {
                            return \array_map(function (ReflectionProperty $property) use ($trait) : ReflectionProperty {
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

        if (null === $filter) {
            return $this->cachedProperties;
        }

        return \array_filter(
            $this->cachedProperties,
            function (ReflectionProperty $property) use ($filter) {
                return $filter & $property->getModifiers();
            }
        );
    }

    /**
     * Get the property called $name.
     *
     * Returns null if property does not exist.
     *
     * @param string $name
     * @return ReflectionProperty|null
     */
    public function getProperty(string $name) : ?ReflectionProperty
    {
        $properties = $this->getProperties();

        if ( ! isset($properties[$name])) {
            return null;
        }

        return $properties[$name];
    }

    /**
     * Does this class have the specified property?
     *
     * @param string $name
     * @return bool
     */
    public function hasProperty(string $name) : bool
    {
        return null !== $this->getProperty($name);
    }

    public function getDefaultProperties() : array
    {
        return \array_map(
            function (ReflectionProperty $property) {
                return $property->getDefaultValue();
            },
            \array_filter($this->getProperties(), function (ReflectionProperty $property) : bool {
                return $property->isDefault();
            })
        );
    }

    /**
     * @return string|null
     */
    public function getFileName() : ?string
    {
        return $this->locatedSource->getFileName();
    }

    /**
     * @return LocatedSource
     */
    public function getLocatedSource() : LocatedSource
    {
        return $this->locatedSource;
    }

    /**
     * Get the line number that this class starts on.
     *
     * @return int
     */
    public function getStartLine() : int
    {
        return (int) $this->node->getAttribute('startLine', -1);
    }

    /**
     * Get the line number that this class ends on.
     *
     * @return int
     */
    public function getEndLine() : int
    {
        return (int) $this->node->getAttribute('endLine', -1);
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
     * You may optionally specify a source locator that will be used to locate
     * the parent class. If no source locator is given, a default will be used.
     *
     * @return ReflectionClass|null
     *
     * @throws \Rector\BetterReflection\Reflection\Exception\NotAClassReflection
     */
    public function getParentClass() : ?ReflectionClass
    {
        if ( ! ($this->node instanceof ClassNode) || null === $this->node->extends) {
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
        return \array_map(function (self $parentClass) : string {
            return $parentClass->getName();
        }, \array_slice(\array_reverse($this->getInheritanceClassHierarchy()), 1));
    }

    /**
     * @return string
     */
    public function getDocComment() : string
    {
        return GetFirstDocComment::forNode($this->node);
    }

    public function isAnonymous() : bool
    {
        return null === $this->node->name;
    }

    /**
     * Is this an internal class?
     *
     * @return bool
     */
    public function isInternal() : bool
    {
        return $this->locatedSource->isInternal();
    }

    /**
     * Is this a user-defined function (will always return the opposite of
     * whatever isInternal returns).
     *
     * @return bool
     */
    public function isUserDefined() : bool
    {
        return ! $this->isInternal();
    }

    /**
     * Is this class an abstract class.
     *
     * @return bool
     */
    public function isAbstract() : bool
    {
        return $this->node instanceof ClassNode && $this->node->isAbstract();
    }

    /**
     * Is this class a final class.
     *
     * @return bool
     */
    public function isFinal() : bool
    {
        return $this->node instanceof ClassNode && $this->node->isFinal();
    }

    /**
     * Get the core-reflection-compatible modifier values.
     *
     * @return int
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
     *
     * @return bool
     */
    public function isTrait() : bool
    {
        return $this->node instanceof TraitNode;
    }

    /**
     * Is this reflection an interface?
     *
     * @return bool
     */
    public function isInterface() : bool
    {
        return $this->node instanceof InterfaceNode;
    }

    /**
     * Get the traits used, if any are defined. If this class does not have any
     * defined traits, this will return an empty array.
     *
     * You may optionally specify a source locator that will be used to locate
     * the traits. If no source locator is given, a default will be used.
     *
     * @return ReflectionClass[]
     */
    public function getTraits() : array
    {
        return \array_map(
            function (Node\Name $importedTrait) : ReflectionClass {
                return $this->reflectClassForNamedNode($importedTrait);
            },
            \array_merge(
                [],
                ...\array_map(
                    function (TraitUse $traitUse) : array {
                        return $traitUse->traits;
                    },
                    \array_filter($this->node->stmts, function (Node $node) : bool {
                        return $node instanceof TraitUse;
                    })
                )
            )
        );
    }

    /**
     * Given an AST Node\Name, create a new ReflectionClass for the element.
     *
     * @param Node\Name $node
     * @return ReflectionClass
     */
    private function reflectClassForNamedNode(Node\Name $node) : self
    {
        // @TODO use actual `ClassReflector` or `FunctionReflector`?
        /** @var self $class */
        $class = $this->reflector->reflect($node->toString());

        return $class;
    }

    /**
     * Get the names of the traits used as an array of strings, if any are
     * defined. If this class does not have any defined traits, this will
     * return an empty array.
     *
     * You may optionally specify a source locator that will be used to locate
     * the traits. If no source locator is given, a default will be used.
     *
     * @return string[]
     */
    public function getTraitNames() : array
    {
        return \array_map(
            function (ReflectionClass $trait) : string {
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
     *
     * @return string[]
     */
    public function getTraitAliases() : array
    {
        /** @var Node\Stmt\TraitUse[] $traitUsages */
        $traitUsages = \array_filter($this->node->stmts, function (Node $node) : bool {
            return $node instanceof TraitUse;
        });

        $resolvedAliases = [];

        foreach ($traitUsages as $traitUsage) {
            $traitNames  = $traitUsage->traits;
            $adaptations = $traitUsage->adaptations;

            foreach ($adaptations as $adaptation) {
                $usedTrait = $adaptation->trait;
                if (null === $usedTrait) {
                    $usedTrait = $traitNames[0];
                }

                if (empty($adaptation->newName)) {
                    continue;
                }

                $resolvedAliases[$adaptation->newName] = \sprintf(
                    '%s::%s',
                    $usedTrait->toString(),
                    $adaptation->method
                );
            }
        }

        return $resolvedAliases;
    }

    /**
     * Gets the interfaces.
     *
     * @link http://php.net/manual/en/reflectionclass.getinterfaces.php
     *
     * @return ReflectionClass[] An associative array of interfaces, with keys as interface names and the array
     *                           values as {@see ReflectionClass} objects.
     */
    public function getInterfaces() : array
    {
        return \array_merge(...\array_map(
            function (self $reflectionClass) : array {
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
     * @link http://php.net/manual/en/reflectionclass.getinterfacenames.php
     *
     * @return string[] A numerical array with interface names as the values.
     */
    public function getInterfaceNames() : array
    {
        return \array_values(\array_map(
            function (self $interface) : string {
                return $interface->getName();
            },
            $this->getInterfaces()
        ));
    }

    /**
     * Checks whether the given object is an instance.
     *
     * @link http://php.net/manual/en/reflectionclass.isinstance.php
     *
     * @param object $object
     *
     * @return bool
     *
     * @throws NotAnObject
     */
    public function isInstance($object) : bool
    {
        if ( ! \is_object($object)) {
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
     * @link http://php.net/manual/en/reflectionclass.isinstance.php
     *
     * @param string $className
     *
     * @return bool
     */
    public function isSubclassOf(string $className) : bool
    {
        return \in_array(
            \ltrim($className, '\\'),
            \array_map(
                function (self $reflectionClass) : string {
                    return $reflectionClass->getName();
                },
                \array_slice(\array_reverse($this->getInheritanceClassHierarchy()), 1)
            ),
            true
        );
    }

    /**
     * Checks whether this class implements the given interface.
     *
     * @link http://php.net/manual/en/reflectionclass.implementsinterface.php
     *
     * @param string $interfaceName
     *
     * @return bool
     */
    public function implementsInterface(string $interfaceName) : bool
    {
        return \in_array(\ltrim($interfaceName, '\\'), $this->getInterfaceNames(), true);
    }

    /**
     * Checks whether this reflection is an instantiable class
     *
     * @link http://php.net/manual/en/reflectionclass.isinstantiable.php
     *
     * @return bool
     */
    public function isInstantiable() : bool
    {
        // @TODO doesn't consider internal non-instantiable classes yet.
        return ! ($this->isAbstract() || $this->isInterface() || $this->isTrait());
    }

    /**
     * Checks whether this is a reflection of a class that supports the clone operator
     *
     * @link http://php.net/manual/en/reflectionclass.iscloneable.php
     *
     * @return bool
     */
    public function isCloneable() : bool
    {
        if ( ! $this->isInstantiable()) {
            return false;
        }

        if ( ! $this->hasMethod('__clone')) {
            return true;
        }

        return $this->getMethod('__clone')->isPublic();
    }

    /**
     * Checks if iterateable
     *
     * @link http://php.net/manual/en/reflectionclass.isiterateable.php
     *
     * @return bool
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
            return \array_merge(
                [],
                ...\array_map(
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
            ? \array_slice($this->getInterfacesHierarchy(), 1)
            : [];
    }

    /**
     * @return ReflectionClass[] ordered from inheritance root to leaf (this class)
     */
    private function getInheritanceClassHierarchy() : array
    {
        $parentClass = $this->getParentClass();

        return $parentClass
            ? \array_merge($parentClass->getInheritanceClassHierarchy(), [$this])
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
        if ( ! $this->isInterface()) {
            throw NotAnInterfaceReflection::fromReflectionClass($this);
        }

        /** @var InterfaceNode $node */
        $node = $this->node;

        return \array_merge(
            [$this->getName() => $this],
            ...\array_map(
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
        throw Uncloneable::fromClass(\get_class($this));
    }

    /**
     * Get the value of a static property, if it exists. Throws a
     * PropertyDoesNotExist exception if it does not exist or is not static.
     * (note, differs very slightly from internal reflection behaviour)
     *
     * @param string $propertyName
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

        if ( ! $property || ! $property->isStatic()) {
            throw PropertyDoesNotExist::fromName($propertyName);
        }

        return $property->getValue();
    }

    /**
     * Set the value of a static property
     *
     * @param string $propertyName
     * @param mixed $value
     * @return void
     *
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     */
    public function setStaticPropertyValue(string $propertyName, $value) : void
    {
        $property = $this->getProperty($propertyName);

        if ( ! $property || ! $property->isStatic()) {
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
            if ( ! $property->isStatic()) {
                continue;
            }

            $staticProperties[$property->getName()] = $property->getValue();
        }

        return $staticProperties;
    }

    /**
     * Retrieve the AST node for this class
     *
     * @return ClassLikeNode
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
     * @param bool $isFinal
     * @throws \Rector\BetterReflection\Reflection\Exception\NotAClassReflection
     */
    public function setFinal(bool $isFinal) : void
    {
        if ( ! $this->node instanceof ClassNode) {
            throw NotAClassReflection::fromReflectionClass($this);
        }

        if (true === $isFinal) {
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
     *
     * @param string $methodName
     * @return bool
     */
    public function removeMethod(string $methodName) : bool
    {
        $lowerName = \strtolower($methodName);
        foreach ($this->node->stmts as $key => $stmt) {
            if ($stmt instanceof ClassMethod && $lowerName === \strtolower($stmt->name)) {
                unset($this->node->stmts[$key], $this->cachedMethods);
                return true;
            }
        }
        return false;
    }

    /**
     * Add a new method to the class.
     *
     * @param string $methodName
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
     *
     * @param string $propertyName
     * @param int $visibility
     * @param bool $static
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
     *
     * @param string $propertyName
     * @return bool
     */
    public function removeProperty(string $propertyName) : bool
    {
        $lowerName = \strtolower($propertyName);

        foreach ($this->node->stmts as $key => $stmt) {
            if ($stmt instanceof PropertyNode) {
                $propertyNames = \array_map(function (Node\Stmt\PropertyProperty $propertyProperty) : string {
                    return \strtolower($propertyProperty->name);
                }, $stmt->props);

                if (\in_array($lowerName, $propertyNames, true)) {
                    $this->cachedProperties          = null;
                    $this->cachedImmediateProperties = null;
                    unset($this->node->stmts[$key]);

                    return true;
                }
            }
        }

        return false;
    }
}
