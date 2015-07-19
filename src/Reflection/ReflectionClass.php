<?php

namespace BetterReflection\Reflection;

use BetterReflection\NodeCompiler\CompileNodeToValue;
use BetterReflection\Reflection\Exception\NoParent;
use BetterReflection\Reflection\Exception\NotAnObject;
use BetterReflection\Reflection\Exception\NotAString;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\AutoloadSourceLocator;
use BetterReflection\SourceLocator\LocatedSource;
use BetterReflection\SourceLocator\SourceLocator;
use BetterReflection\TypesFinder\FindTypeFromAst;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Types\Object_;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use PhpParser\Node\Stmt\ClassLike as ClassLikeNode;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\Trait_ as TraitNode;
use PhpParser\Node\Stmt\Interface_ as InterfaceNode;
use PhpParser\Node\Stmt\ClassConst as ConstNode;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\Node\Stmt\TraitUse;

class ReflectionClass implements Reflection
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var NamespaceNode
     */
    private $declaringNamespace;

    /**
     * @var ReflectionMethod[]
     */
    private $methods = [];

    /**
     * @var mixed[]
     */
    private $constants = [];

    /**
     * @var ReflectionProperty[]
     */
    private $properties = [];

    /**
     * @var LocatedSource
     */
    private $locatedSource;

    /**
     * @var Fqsen|null
     */
    private $extendsClassType;

    /**
     * @var ClassLikeNode
     */
    private $node;

    private function __construct()
    {
    }

    public static function createFromName($className)
    {
        return (new ClassReflector(new AutoloadSourceLocator()))->reflect($className);
    }

    /**
     * Create from a Class Node.
     *
     * @param ClassLikeNode $node
     * @param LocatedSource $locatedSource
     * @param NamespaceNode|null $namespace optional - if omitted, we assume it is global namespaced class
     * @return ReflectionClass
     */
    public static function createFromNode(
        ClassLikeNode $node,
        LocatedSource $locatedSource,
        NamespaceNode $namespace = null
    ) {
        $class = new self();
        $class->node = $node;

        $class->locatedSource = $locatedSource;
        $class->name = $node->name;

        if (null !== $namespace) {
            $class->declaringNamespace = $namespace;
        }

        if ($node instanceof ClassNode && null !== $node->extends) {
            $objectType = (new FindTypeFromAst())->__invoke($node->extends, $locatedSource, $class->getNamespaceName());
            if (null !== $objectType && $objectType instanceof Object_) {
                $class->extendsClassType = $objectType->getFqsen();
            }
        }

        $methodNodes = $node->getMethods();

        foreach ($methodNodes as $methodNode) {
            $class->methods[] = ReflectionMethod::createFromNode(
                $methodNode,
                $class
            );
        }

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ConstNode) {
                $constName = $stmt->consts[0]->name;
                $constValue = (new CompileNodeToValue())->__invoke($stmt->consts[0]->value);
                $class->constants[$constName] = $constValue;
            }

            if ($stmt instanceof PropertyNode) {
                $prop = ReflectionProperty::createFromNode($stmt, $class);
                $class->properties[$prop->getName()] = $prop;
            }
        }

        return $class;
    }

    /**
     * Get the "short" name of the class (e.g. for A\B\Foo, this will return
     * "Foo").
     *
     * @return string
     */
    public function getShortName()
    {
        return $this->name;
    }

    /**
     * Get the "full" name of the class (e.g. for A\B\Foo, this will return
     * "A\B\Foo").
     *
     * @return string
     */
    public function getName()
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
    public function getNamespaceName()
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
    public function inNamespace()
    {
        return null !== $this->declaringNamespace
            && null !== $this->declaringNamespace->name;
    }

    /**
     * Fetch an array of all methods for this class.
     *
     * @return ReflectionMethod[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Get a single method with the name $methodName.
     *
     * @param string $methodName
     * @return ReflectionMethod
     */
    public function getMethod($methodName)
    {
        foreach ($this->getMethods() as $method) {
            if ($method->getName() === $methodName) {
                return $method;
            }
        }

        throw new \OutOfBoundsException(
            'Could not find method: ' . $methodName
        );
    }

    /**
     * Does the class have the specified method method?
     *
     * @param string $methodName
     * @return bool
     */
    public function hasMethod($methodName)
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
    public function getConstants()
    {
        return $this->constants;
    }

    /**
     * Get the value of the specified class constant.
     *
     * Returns null if not specified.
     *
     * @param string $name
     * @return mixed|null
     */
    public function getConstant($name)
    {
        if (!$this->hasConstant($name)) {
            return null;
        }

        return $this->constants[$name];
    }

    /**
     * Does this class have the specified constant?
     *
     * @param string $name
     * @return bool
     */
    public function hasConstant($name)
    {
        return isset($this->constants[$name]);
    }

    /**
     * Get the constructor method for this class.
     *
     * @return ReflectionMethod
     */
    public function getConstructor()
    {
        return $this->getMethod('__construct');
    }

    /**
     * Get the properties for this class.
     *
     * @return ReflectionProperty[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Get the property called $name.
     *
     * Returns null if property does not exist.
     *
     * @param string $name
     * @return ReflectionProperty|null
     */
    public function getProperty($name)
    {
        if (!$this->hasProperty($name)) {
            return null;
        }

        return $this->properties[$name];
    }

    /**
     * Does this class have the specified property?
     *
     * @param string $name
     * @return bool
     */
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * Return an array with default properties (properties that were defined at
     * compile-time rather than at run time).
     *
     * @return ReflectionProperty[]
     */
    public function getDefaultProperties()
    {
        return array_filter($this->getProperties(), function (ReflectionProperty $property) {
            return $property->isDefault();
        });
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->locatedSource->getFileName();
    }

    /**
     * @return LocatedSource
     */
    public function getLocatedSource()
    {
        return $this->locatedSource;
    }

    /**
     * Get the line number that this class starts on.
     *
     * @return int
     */
    public function getStartLine()
    {
        return (int)$this->node->getAttribute('startLine', -1);
    }

    /**
     * Get the line number that this class ends on.
     *
     * @return int
     */
    public function getEndLine()
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
     * @param SourceLocator|null $sourceLocator
     * @return ReflectionClass
     * @throws NoParent
     */
    public function getParentClass(SourceLocator $sourceLocator = null)
    {
        if (null === $this->extendsClassType) {
            return null;
        }

        $fqsen = $this->extendsClassType->__toString();

        if (null !== $sourceLocator) {
            return (new ClassReflector($sourceLocator))->reflect($fqsen);
        }

        return self::createFromName($fqsen);
    }

    /**
     * @return string
     */
    public function getDocComment()
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
     * Note - we cannot reflect on internal classes (as there is no PHP source
     * code we can access. This means, at present, we can only EVER return false
     * from this function.
     *
     * @see https://github.com/Roave/BetterReflection/issues/38
     * @return bool
     */
    public function isInternal()
    {
        return false;
    }

    /**
     * Is this a user-defined function (will always return the opposite of
     * whatever isInternal returns).
     *
     * @return bool
     */
    public function isUserDefined()
    {
        return !$this->isInternal();
    }

    /**
     * Is this class an abstract class.
     *
     * @return bool
     */
    public function isAbstract()
    {
        return $this->node instanceof ClassNode && $this->node->isAbstract();
    }

    /**
     * Is this class a final class.
     *
     * @return bool
     */
    public function isFinal()
    {
        return $this->node instanceof ClassNode && $this->node->isFinal();
    }

    /**
     * Get the core-reflection-compatible modifier values.
     *
     * @return int
     */
    public function getModifiers()
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
    public function isTrait()
    {
        return $this->node instanceof TraitNode;
    }

    /**
     * Is this reflection an interface?
     *
     * @return bool
     */
    public function isInterface()
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
     * @param SourceLocator|null $sourceLocator
     * @return ReflectionClass[]
     */
    public function getTraits(SourceLocator $sourceLocator = null)
    {
        $traitUsages = array_filter($this->node->stmts, function (Node $node) {
            return $node instanceof TraitUse;
        });

        $traitNameNodes = [];
        foreach ($traitUsages as $traitUsage) {
            $traitNameNodes = array_merge($traitNameNodes, $traitUsage->traits);
        }

        return array_map(function (Node\Name $importedTrait) use ($sourceLocator) {
            return $this->reflectClassForNamedNode($importedTrait, $sourceLocator);
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
    private function getFqsenFromNamedNode(Node\Name $node)
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
     * @param SourceLocator|null $sourceLocator
     * @return ReflectionClass
     */
    private function reflectClassForNamedNode(Node\Name $node, SourceLocator $sourceLocator = null)
    {
        $fqsen = $this->getFqsenFromNamedNode($node);

        if (null !== $sourceLocator) {
            return (new ClassReflector($sourceLocator))->reflect($fqsen);
        }

        return self::createFromName($fqsen);
    }

    /**
     * Get the names of the traits used as an array of strings, if any are
     * defined. If this class does not have any defined traits, this will
     * return an empty array.
     *
     * You may optionally specify a source locator that will be used to locate
     * the traits. If no source locator is given, a default will be used.
     *
     * @param SourceLocator|null $sourceLocator
     * @return string[]
     */
    public function getTraitNames(SourceLocator $sourceLocator = null)
    {
        return array_map(
            function (ReflectionClass $trait) {
                return $trait->getName();
            },
            $this->getTraits($sourceLocator)
        );
    }

    /**
     * Return a list of the aliases used when importing traits for this class.
     * The returned array is in key/value pair in this format:
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
    public function getTraitAliases()
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
     * Gets the interfaces
     *
     * @link http://php.net/manual/en/reflectionclass.getinterfaces.php
     *
     * @param SourceLocator|null $sourceLocator a source locator - if none is provided, an autoloader-based locator
     *                                          will be used
     *
     * @return ReflectionClass[] An associative array of interfaces, with keys as interface names and the array
     *                           values as {@see ReflectionClass} objects.
     */
    public function getInterfaces(SourceLocator $sourceLocator = null)
    {
        return array_merge(...array_map(
            function (self $reflectionClass) use ($sourceLocator) {
                return $reflectionClass->getCurrentClassImplementedInterfacesIndexedByName($sourceLocator);
            },
            $this->getInheritanceClassHierarchy($sourceLocator)
        ));
    }

    /**
     * Gets the interface names
     *
     * @link http://php.net/manual/en/reflectionclass.getinterfacenames.php
     *
     * @param SourceLocator|null $sourceLocator a source locator - if none is provided, an autoloader-based locator
     *                                          will be used
     *
     * @return string[] A numerical array with interface names as the values.
     */
    public function getInterfaceNames(SourceLocator $sourceLocator = null)
    {
        return array_values(array_map(
            function (self $interface) {
                return $interface->getName();
            },
            $this->getInterfaces($sourceLocator)
        ));
    }

    /**
     * Checks whether the given object is an instance
     *
     * @link http://php.net/manual/en/reflectionclass.isinstance.php
     *
     * @param object $object
     *
     * @return bool
     *
     * @throws NotAnObject
     */
    public function isInstance($object)
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
     * Checks whether the given object is an instance
     *
     * @link http://php.net/manual/en/reflectionclass.isinstance.php
     *
     * @param string        $className
     * @param SourceLocator $sourceLocator
     *
     * @return bool
     */
    public function isSubclassOf($className, SourceLocator $sourceLocator)
    {
        if (! is_string($className)) {
            throw NotAString::fromNonString($className);
        }

        return in_array(
            ltrim($className, '\\'),
            array_map(
                function (self $reflectionClass) {
                    return $reflectionClass->getName();
                },
                array_slice(array_reverse($this->getInheritanceClassHierarchy($sourceLocator)), 1)
            ),
            true
        );
    }

    /**
     * @param SourceLocator $sourceLocator
     *
     * @return ReflectionClass[] indexed by interface name
     */
    private function getCurrentClassImplementedInterfacesIndexedByName(SourceLocator $sourceLocator)
    {
        /* @var $interfaces ReflectionClass[] */
        $interfaces = array_map(
            function (Node\Name $interfaceName) use ($sourceLocator) {
                return $this->reflectClassForNamedNode($interfaceName, $sourceLocator);
            },
            $this->node instanceof ClassNode ? $this->node->implements : []
        );

        $interfacesByName = [];

        foreach ($interfaces as $interface) {
            $interfacesByName[$interface->getName()] = $interface;
        }

        return $interfacesByName;
    }

    /**
     * @return ReflectionClass[] ordered from inheritance root to leaf (this class)
     */
    private function getInheritanceClassHierarchy(SourceLocator $sourceLocator)
    {
        $parentClass = $this->getParentClass($sourceLocator);

        return $parentClass
            ? array_merge($parentClass->getInheritanceClassHierarchy($sourceLocator), [$this])
            : [$this];
    }
}
