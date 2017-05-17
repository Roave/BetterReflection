<?php

namespace Roave\BetterReflection\Reflection;

use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\Exception\NotAClassReflection;
use Roave\BetterReflection\Reflection\Exception\NotAnInterfaceReflection;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\TypesFinder\FindTypeFromAst;
use phpDocumentor\Reflection\Types\Object_;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use PhpParser\Node\Stmt\ClassLike as ClassLikeNode;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\Trait_ as TraitNode;
use PhpParser\Node\Stmt\Interface_ as InterfaceNode;
use PhpParser\Node\Stmt\ClassConst as ConstNode;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\Node\Stmt\TraitUse;

class ReflectionClass implements Reflection, \Reflector
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var NamespaceNode
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
     * @var mixed[]|null
     */
    private $cachedConstants;

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
            throw new \InvalidArgumentException('Class name must be provided');
        }

        $reflection = self::createFromName($className);
        return $reflection->__toString();
    }

    /**
     * Get a string representation of this reflection
     *
     * @todo Refactor this
     * @see https://github.com/Roave/BetterReflection/issues/94
     *
     * @return string
     */
    public function __toString() : string
    {
        $isObject = $this instanceof ReflectionObject;

        $format  = "%s [ <user> class %s%s%s ] {\n";
        $format .= "  @@ %s %d-%d\n\n";
        $format .= "  - Constants [%d] {%s\n  }\n\n";
        $format .= "  - Static properties [%d] {%s\n  }\n\n";
        $format .= "  - Static methods [%d] {%s\n  }\n\n";
        $format .= "  - Properties [%d] {%s\n  }\n\n";
        $format .= ($isObject ? "  - Dynamic properties [%d] {%s\n  }\n\n" : '%s%s');
        $format .= "  - Methods [%d] {%s\n  }\n";
        $format .= "}\n";

        $staticProperties = array_filter($this->getProperties(), function (ReflectionProperty $property) {
            return $property->isStatic();
        });
        $staticMethods = array_filter($this->getMethods(), function (ReflectionMethod $method) {
            return $method->isStatic();
        });
        $defaultProperties = array_filter($this->getProperties(), function (ReflectionProperty $property) {
            return !$property->isStatic() && $property->isDefault();
        });
        $dynamicProperties = array_filter($this->getProperties(), function (ReflectionProperty $property) {
            return !$property->isStatic() && !$property->isDefault();
        });
        $methods = array_filter($this->getMethods(), function (ReflectionMethod $method) {
            return !$method->isStatic();
        });

        $buildString = function (array $items, $indentLevel = 4) {
            if (!count($items)) {
                return '';
            }
            $indent = "\n" . str_repeat(' ', $indentLevel);
            return $indent . implode($indent, explode("\n", implode("\n", $items)));
        };

        $buildConstants = function (array $items, $indentLevel = 4) {
            $str = '';

            foreach ($items as $name => $value) {
                $str .= "\n" . str_repeat(' ', $indentLevel);
                $str .= sprintf(
                    'Constant [ %s %s ] { %s }',
                    gettype($value),
                    $name,
                    $value
                );
            }

            return $str;
        };

        $interfaceNames = $this->getInterfaceNames();

        $str = sprintf(
            $format,
            ($isObject ? 'Object of class' : 'Class'),
            $this->getName(),
            null !== $this->getParentClass() ? (' extends ' . $this->getParentClass()->getName()) : '',
            count($interfaceNames) ? (' implements ' . implode(', ', $interfaceNames)) : '',
            $this->getFileName(),
            $this->getStartLine(),
            $this->getEndLine(),
            count($this->getConstants()),
            $buildConstants($this->getConstants()),
            count($staticProperties),
            $buildString($staticProperties),
            count($staticMethods),
            $buildString($staticMethods),
            count($defaultProperties),
            $buildString($defaultProperties),
            $isObject ? count($dynamicProperties) : '',
            $isObject ? $buildString($dynamicProperties) : '',
            count($methods),
            $buildString($methods)
        );

        return $str;
    }

    /**
     * Create a ReflectionClass by name, using default reflectors etc.
     *
     * @param string $className
     * @return ReflectionClass
     */
    public static function createFromName(string $className)
    {
        return ClassReflector::buildDefaultReflector()->reflect($className);
    }

    /**
     * Create a ReflectionClass from an instance, using default reflectors etc.
     *
     * This is simply a helper method that calls ReflectionObject::createFromInstance().
     *
     * @see ReflectionObject::createFromInstance
     * @param object $instance
     * @return ReflectionClass
     * @throws \InvalidArgumentException
     */
    public static function createFromInstance($instance)
    {
        if (! is_object($instance)) {
            throw new \InvalidArgumentException('Instance must be an instance of an object');
        }

        return ReflectionObject::createFromInstance($instance);
    }

    /**
     * Create from a Class Node.
     *
     * @param Reflector          $reflector
     * @param ClassLikeNode      $node
     * @param LocatedSource      $locatedSource
     * @param NamespaceNode|null $namespace optional - if omitted, we assume it is global namespaced class
     *
     * @return ReflectionClass
     */
    public static function createFromNode(
        Reflector $reflector,
        ClassLikeNode $node,
        LocatedSource $locatedSource,
        NamespaceNode $namespace = null
    ) {
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
        return $this->node->name;
    }

    /**
     * Get the "full" name of the class (e.g. for A\B\Foo, this will return
     * "A\B\Foo").
     *
     * @return string
     */
    public function getName() : string
    {
        if (!$this->inNamespace()) {
            return $this->getShortName();
        }

        return $this->getNamespaceName() . '\\' . $this->getShortName();
    }

    /**
     * Get the "namespace" name of the class (e.g. for A\B\Foo, this will
     * return "A\B").
     *
     * @return string
     */
    public function getNamespaceName() : string
    {
        if (!$this->inNamespace()) {
            return '';
        }

        return implode('\\', $this->declaringNamespace->name->parts);
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

    /**
     * Construct a flat list of methods that are available. This will search up
     * all parent classes/traits/interfaces/current scope for methods.
     *
     * @return ReflectionMethod[]
     */
    private function scanMethods() : array
    {
        // merging together methods from interfaces, parent class, traits, current class (in this precise order)
        /* @var $inheritedMethods \ReflectionMethod[] */
        $inheritedMethods = array_merge(
            array_merge(
                [],
                ...array_map(
                    function (ReflectionClass $ancestor) {
                        return $ancestor->getMethods();
                    },
                    array_values(array_merge(
                        $this->getInterfaces(),
                        array_filter([$this->getParentClass()]),
                        $this->getTraits()
                    ))
                )
            ),
            array_map(
                function (ClassMethod $methodNode) {
                    return ReflectionMethod::createFromNode($this->reflector, $methodNode, $this);
                },
                $this->node->getMethods()
            )
        );

        $methodsByName = [];

        foreach ($inheritedMethods as $inheritedMethod) {
            $methodsByName[$inheritedMethod->getName()] = $inheritedMethod;
        }

        return $methodsByName;
    }

    /**
     * @return ReflectionMethod[] indexed by method name
     */
    private function getMethodsIndexedByName() : array
    {
        if (! isset($this->cachedMethods)) {
            $this->cachedMethods = $this->scanMethods();
        }

        return $this->cachedMethods;
    }

    /**
     * Fetch an array of all methods for this class.
     *
     * @param ?int
     * @return ReflectionMethod[]
     */
    public function getMethods(?int $filter = null) : array
    {
        if (null === $filter) {
            return array_values($this->getMethodsIndexedByName());
        }

        return array_values(
            array_filter(
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
     * @return ReflectionMethod[]
     */
    public function getImmediateMethods(?int $filter = null) : array
    {
        /* @var $methods \ReflectionMethod[] */
        $methods = array_map(
            function (ClassMethod $methodNode) {
                return ReflectionMethod::createFromNode($this->reflector, $methodNode, $this);
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

        if (! isset($methods[$methodName])) {
            throw new \OutOfBoundsException('Could not find method: ' . $methodName);
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
        } catch (\OutOfBoundsException $exception) {
            return false;
        }
    }

    /**
     * Get an array of the defined constants in this class.
     *
     * @return mixed[]
     */
    public function getConstants() : array
    {
        if (null !== $this->cachedConstants) {
            return $this->cachedConstants;
        }

        $constants = [];
        foreach ($this->node->stmts as $stmt) {
            if ($stmt instanceof ConstNode) {
                $constName = $stmt->consts[0]->name;
                $constValue = (new CompileNodeToValue())->__invoke(
                    $stmt->consts[0]->value,
                    new CompilerContext($this->reflector, $this)
                );
                $constants[$constName] = $constValue;
            }
        }

        $this->cachedConstants = $constants;
        return $constants;
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
        $constants = $this->getConstants();

        if (!isset($constants[$name])) {
            return null;
        }

        return $constants[$name];
    }

    /**
     * Does this class have the specified constant?
     *
     * @param string $name
     * @return bool
     */
    public function hasConstant(string $name) : bool
    {
        return null !== $this->getConstant($name);
    }

    /**
     * Get the constructor method for this class.
     *
     * @return ReflectionMethod
     * @throws \OutOfBoundsException
     */
    public function getConstructor() : ReflectionMethod
    {
        return $this->getMethod('__construct');
    }

    /**
     * Get only the properties for this specific class (i.e. do not search
     * up parent classes etc.)
     *
     * @return ReflectionProperty[]
     */
    public function getImmediateProperties() : array
    {
        if (null !== $this->cachedProperties) {
            return $this->cachedProperties;
        }

        $properties = [];
        foreach ($this->node->stmts as $stmt) {
            if ($stmt instanceof PropertyNode) {
                $prop = ReflectionProperty::createFromNode($this->reflector, $stmt, $this);
                $properties[$prop->getName()] = $prop;
            }
        }

        return $this->cachedProperties = $properties;
    }

    /**
     * Get the properties for this class.
     *
     * @return ReflectionProperty[]
     */
    public function getProperties() : array
    {
        // merging together properties from parent class, traits, current class (in this precise order)
        return array_merge(
            array_merge(
                [],
                ...array_map(
                    function (ReflectionClass $ancestor) {
                        return array_filter(
                            $ancestor->getProperties(),
                            function (ReflectionProperty $property) {
                                return !$property->isPrivate();
                            }
                        );
                    },
                    array_filter([$this->getParentClass()])
                ),
                ...array_map(
                    function (ReflectionClass $trait) {
                        return $trait->getProperties();
                    },
                    $this->getTraits()
                )
            ),
            $this->getImmediateProperties()
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

        if (!isset($properties[$name])) {
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
        return array_map(
            function (ReflectionProperty $property) {
                return $property->getDefaultValue();
            },
            array_filter($this->getProperties(), function (ReflectionProperty $property) {
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
        return (int)$this->node->getAttribute('startLine', -1);
    }

    /**
     * Get the line number that this class ends on.
     *
     * @return int
     */
    public function getEndLine() : int
    {
        return (int)$this->node->getAttribute('endLine', -1);
    }

    /**
     * Get the parent class, if it is defined. If this class does not have a
     * specified parent class, this will throw an exception.
     *
     * You may optionally specify a source locator that will be used to locate
     * the parent class. If no source locator is given, a default will be used.
     *
     * @return ReflectionClass|null
     */
    public function getParentClass()
    {
        if (!($this->node instanceof ClassNode) || null === $this->node->extends) {
            return null;
        }

        $objectType = (new FindTypeFromAst())->__invoke($this->node->extends, $this->locatedSource, $this->getNamespaceName());
        if (null === $objectType || !($objectType instanceof Object_)) {
            return null;
        }

        // @TODO use actual `ClassReflector` or `FunctionReflector`?
        /* @var $parent self */
        $parent = $this->reflector->reflect((string)$objectType->getFqsen());

        if ($parent->isInterface() || $parent->isTrait()) {
            throw NotAClassReflection::fromReflectionClass($parent);
        }

        return $parent;
    }

    /**
     * @return string
     */
    public function getDocComment() : string
    {
        if (!$this->node->hasAttribute('comments')) {
            return '';
        }

        /* @var \PhpParser\Comment\Doc $comment */
        $comment = $this->node->getAttribute('comments')[0];
        return $comment->getReformattedText();
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
        return !$this->isInternal();
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
        $val = 0;
        $val += $this->isAbstract() ? \ReflectionClass::IS_EXPLICIT_ABSTRACT : 0;
        $val += $this->isFinal() ? \ReflectionClass::IS_FINAL : 0;
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
        $traitUsages = array_filter($this->node->stmts, function (Node $node) {
            return $node instanceof TraitUse;
        });

        $traitNameNodes = [];
        foreach ($traitUsages as $traitUsage) {
            $traitNameNodes = array_merge($traitNameNodes, $traitUsage->traits);
        }

        return array_map(function (Node\Name $importedTrait) {
            return $this->reflectClassForNamedNode($importedTrait);
        }, $traitNameNodes);
    }

    /**
     * Given an AST Node\Name, try to resolve the type into a fully qualified
     * structural element name (FQSEN).
     *
     * @param Node\Name $node
     * @return string
     * @throws \Exception
     */
    private function getFqsenFromNamedNode(Node\Name $node) : string
    {
        $objectType = (new FindTypeFromAst())->__invoke($node, $this->locatedSource, $this->getNamespaceName());
        if (null === $objectType || !($objectType instanceof Object_)) {
            throw new \Exception('Unable to determine FQSEN for named node');
        }

        return $objectType->getFqsen()->__toString();
    }

    /**
     * Given an AST Node\Name, create a new ReflectionClass for the element.
     * This should work with traits, interfaces and classes alike, as long as
     * the FQSEN resolves to something that exists.
     *
     * You may optionally specify a source locator that will be used to locate
     * the traits. If no source locator is given, a default will be used.
     *
     * @param Node\Name $node
     * @return ReflectionClass
     */
    private function reflectClassForNamedNode(Node\Name $node) : self
    {
        // @TODO use actual `ClassReflector` or `FunctionReflector`?
        return $this->reflector->reflect($this->getFqsenFromNamedNode($node));
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
        return array_map(
            function (ReflectionClass $trait) {
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
        $traitUsages = array_filter($this->node->stmts, function (Node $node) {
            return $node instanceof TraitUse;
        });

        $resolvedAliases = [];

        /* @var Node\Stmt\TraitUse[] $traitUsages */
        foreach ($traitUsages as $traitUsage) {
            $traitNames = $traitUsage->traits;

            $adaptations = $traitUsage->adaptations;

            foreach ($adaptations as $adaptation) {
                $usedTrait = $adaptation->trait;
                if (null === $usedTrait) {
                    $usedTrait = $traitNames[0];
                }

                if (empty($adaptation->newName)) {
                    continue;
                }

                $resolvedAliases[$adaptation->newName] = sprintf(
                    '%s::%s',
                    ltrim($this->getFqsenFromNamedNode($usedTrait), '\\'),
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
        return array_merge(...array_map(
            function (self $reflectionClass) {
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
        return array_values(array_map(
            function (self $interface) {
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
     * @link http://php.net/manual/en/reflectionclass.isinstance.php
     *
     * @param string $className
     *
     * @return bool
     */
    public function isSubclassOf(string $className) : bool
    {
        return in_array(
            ltrim($className, '\\'),
            array_map(
                function (self $reflectionClass) {
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
     * @link http://php.net/manual/en/reflectionclass.implementsinterface.php
     *
     * @param string $interfaceName
     *
     * @return bool
     */
    public function implementsInterface(string $interfaceName) : bool
    {
        return in_array(ltrim($interfaceName, '\\'), $this->getInterfaceNames(), true);
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
     * @link http://php.net/manual/en/reflectionclass.isiterateable.php
     *
     * @return bool
     */
    public function isIterateable() : bool
    {
        return $this->isInstantiable() && $this->implementsInterface(\Traversable::class);
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
                    function (Node\Name $interfaceName) {
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
     */
    private function getInterfacesHierarchy() : array
    {
        if (! $this->isInterface()) {
            throw NotAnInterfaceReflection::fromReflectionClass($this);
        }

        /* @var $node InterfaceNode */
        $node = $this->node;

        return array_merge(
            [$this->getName() => $this],
            ...array_map(
                function (Node\Name $interfaceName) {
                    return $this
                        ->reflectClassForNamedNode($interfaceName)
                        ->getInterfacesHierarchy();
                },
                $node->extends
            )
        );
    }

    /**
     * @throws Exception\Uncloneable
     */
    public function __clone()
    {
        throw Exception\Uncloneable::fromClass(__CLASS__);
    }

    /**
     * Get the value of a static property, if it exists. Throws a
     * PropertyDoesNotExist exception if it does not exist or is not static.
     * (note, differs very slightly from internal reflection behaviour)
     *
     * @param string $propertyName
     * @return mixed
     */
    public function getStaticPropertyValue(string $propertyName)
    {
        if (!class_exists($this->getName(), false)) {
            throw new Exception\ClassDoesNotExist('Property cannot be retrieved as the class is not loaded');
        }

        if (!$this->hasProperty($propertyName) || !$this->getProperty($propertyName)->isStatic()) {
            throw new Exception\PropertyDoesNotExist('Property does not exist on class or is not static');
        }

        // PHP behaviour is to simply say "property does not exist" if accessing
        // protected or private values. Here we be a little more explicit in
        // reasoning...
        if (!$this->getProperty($propertyName)->isPublic()) {
            throw new Exception\PropertyNotPublic('Property is not public');
        }

        $className = $this->getName();
        return $className::${$propertyName};
    }

    /**
     * Set the value of a static property
     *
     * @param string $propertyName
     * @param mixed $value
     * @return void
     */
    public function setStaticPropertyValue(string $propertyName, $value) : void
    {
        if (!class_exists($this->getName(), false)) {
            throw new Exception\ClassDoesNotExist('Property cannot be set as the class is not loaded');
        }

        if (!$this->hasProperty($propertyName) || !$this->getProperty($propertyName)->isStatic()) {
            throw new Exception\PropertyDoesNotExist('Property does not exist on class or is not static');
        }

        // PHP behaviour is to simply say "property does not exist" if accessing
        // protected or private values. Here we be a little more explicit in
        // reasoning...
        if (!$this->getProperty($propertyName)->isPublic()) {
            throw new Exception\PropertyNotPublic('Property is not public');
        }

        $className = $this->getName();
        $className::${$propertyName} = $value;
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

    /**
     * Set whether this class is final or not
     *
     * @param bool $isFinal
     * @throws \Roave\BetterReflection\Reflection\Exception\NotAClassReflection
     */
    public function setFinal(bool $isFinal) : void
    {
        if (!$this->node instanceof ClassNode) {
            throw Exception\NotAClassReflection::fromReflectionClass($this);
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
     *
     * @param string $methodName
     * @return bool
     */
    public function removeMethod(string $methodName) : bool
    {
        $lowerName = strtolower($methodName);
        foreach ($this->node->stmts as $key => $stmt) {
            if ($stmt instanceof ClassMethod && $lowerName === strtolower($stmt->name)) {
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
        int $visibility = \ReflectionProperty::IS_PUBLIC,
        bool $static = false
    ) : void {
        $type = 0;
        switch($visibility) {
            case \ReflectionProperty::IS_PRIVATE:
                $type |= ClassNode::MODIFIER_PRIVATE;
                break;
            case \ReflectionProperty::IS_PROTECTED:
                $type |= ClassNode::MODIFIER_PROTECTED;
                break;
            default:
                $type |= ClassNode::MODIFIER_PUBLIC;
                break;
        }

        if ($static) {
            $type |= ClassNode::MODIFIER_STATIC;
        }

        $this->node->stmts[] = new PropertyNode($type, [new Node\Stmt\PropertyProperty($propertyName)]);
        $this->cachedProperties = null;
    }

    /**
     * Remove a property from the class.
     *
     * @param string $propertyName
     * @return bool
     */
    public function removeProperty(string $propertyName) : bool
    {
        $lowerName = strtolower($propertyName);

        foreach ($this->node->stmts as $key => $stmt) {
            if ($stmt instanceof PropertyNode) {
                $propertyNames = array_map(function (Node\Stmt\PropertyProperty $propertyProperty) {
                    return strtolower($propertyProperty->name);
                }, $stmt->props);

                if (in_array($lowerName, $propertyNames, true)) {
                    $this->cachedProperties = null;
                    unset($this->node->stmts[$key]);

                    return true;
                }
            }
        }

        return false;
    }
}
