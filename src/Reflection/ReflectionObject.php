<?php

namespace BetterReflection\Reflection;

use BetterReflection\Reflector\ClassReflector;
use BetterReflection\Reflector\Reflector;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\Builder\Property as PropertyNodeBuilder;
use PhpParser\Node\Stmt\ClassLike as ClassLikeNode;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use BetterReflection\SourceLocator\Located\LocatedSource;

class ReflectionObject extends ReflectionClass
{
    /**
     * @var ReflectionClass
     */
    private $reflectionClass;

    /**
     * @var object
     */
    private $object;

    /**
     * @var Reflector
     */
    private $reflector;

    private function __construct(Reflector $reflector, ReflectionClass $reflectionClass, $object)
    {
        $this->reflector = $reflector;
        $this->reflectionClass = $reflectionClass;
        $this->object = $object;
    }

    /**
     * Create a reflection and return the string representation of a class instance
     *
     * @param object $instance
     * @return string
     */
    public static function export($instance = null)
    {
        if (null === $instance) {
            throw new \InvalidArgumentException('Class instance must be provided');
        }

        $reflection = self::createFromInstance($instance);
        return $reflection->__toString();
    }

    /**
     * Cannot instantiate this way, use ReflectionObject::createFromInstance
     *
     * @throws \LogicException
     */
    public static function createFromNode(
        Reflector $reflector,
        ClassLikeNode $node,
        LocatedSource $locatedSource,
        NamespaceNode $namespace = null
    ) {
        throw new \LogicException('Cannot create a ReflectionObject from node - use ReflectionObject::createFromInstance');
    }

    /**
     * Cannot instantiate this way, use ReflectionObject::createFromInstance
     *
     * @throws \LogicException
     */
    public static function createFromName($className)
    {
        throw new \LogicException('Cannot create a ReflectionObject from name - use ReflectionObject::createFromInstance');
    }

    /**
     * Pass an instance of an object to this method to reflect it
     *
     * @param object $object
     * @return ReflectionClass
     */
    public static function createFromInstance($object)
    {
        if (gettype($object) !== 'object') {
            throw new \InvalidArgumentException('Can only create from an instance of an object');
        }

        $reflector = ClassReflector::buildDefaultReflector();
        $reflectionClass = $reflector->reflect(get_class($object));

        return new self($reflector, $reflectionClass, $object);
    }

    /**
     * Reflect on runtime properties for the current instance
     *
     * @return ReflectionProperty[]
     */
    private function getRuntimeProperties()
    {
        if (!$this->reflectionClass->isInstance($this->object)) {
            throw new \InvalidArgumentException('Cannot reflect runtime properties of a separate class');
        }

        // Ensure we have already cached existing properties so we can add to them
        $this->reflectionClass->getProperties();

        // Only known current way is to use internal ReflectionObject to get
        // the runtime-declared properties  :/
        $reflectionProperties = (new \ReflectionObject($this->object))->getProperties();
        $runtimeProperties = [];
        foreach ($reflectionProperties as $property) {
            if ($this->reflectionClass->hasProperty($property->getName())) {
                continue;
            }

            $runtimeProperty = ReflectionProperty::createFromNode(
                $this->reflector,
                $this->createPropertyNodeFromReflection($property, $this->object),
                $this,
                false
            );
            $runtimeProperties[$runtimeProperty->getName()] = $runtimeProperty;
        }
        return $runtimeProperties;
    }

    /**
     * Create an AST PropertyNode given a reflection
     *
     * Note that we don't copy across DocBlock, protected, private or static
     * because runtime properties can't have these attributes.
     *
     * @param \ReflectionProperty $property
     * @param object $instance
     * @return PropertyNode
     */
    private function createPropertyNodeFromReflection(\ReflectionProperty $property, $instance)
    {
        $builder = new PropertyNodeBuilder($property->getName());
        $builder->setDefault($property->getValue($instance));

        if ($property->isPublic()) {
            $builder->makePublic();
        }

        return $builder->getNode();
    }

    /**
     * {@inheritdoc}
     */
    public function getShortName()
    {
        return $this->reflectionClass->getShortName();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->reflectionClass->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespaceName()
    {
        return $this->reflectionClass->getNamespaceName();
    }

    /**
     * {@inheritdoc}
     */
    public function inNamespace()
    {
        return $this->reflectionClass->inNamespace();
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods()
    {
        return $this->reflectionClass->getMethods();
    }

    /**
     * {@inheritdoc}
     */
    public function getImmediateMethods()
    {
        return $this->reflectionClass->getImmediateMethods();
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod($methodName)
    {
        return $this->reflectionClass->getMethod($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMethod($methodName)
    {
        return $this->reflectionClass->hasMethod($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function getConstants()
    {
        return $this->reflectionClass->getConstants();
    }

    /**
     * {@inheritdoc}
     */
    public function getConstant($name)
    {
        return $this->reflectionClass->getConstant($name);
    }

    /**
     * {@inheritdoc}
     */
    public function hasConstant($name)
    {
        return $this->reflectionClass->hasConstant($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getConstructor()
    {
        return $this->reflectionClass->getConstructor();
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return array_merge(
            $this->reflectionClass->getProperties(),
            $this->getRuntimeProperties()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty($name)
    {
        $runtimeProperties = $this->getRuntimeProperties();

        if (isset($runtimeProperties[$name])) {
            return $runtimeProperties[$name];
        }

        return $this->reflectionClass->getProperty($name);
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty($name)
    {
        $runtimeProperties = $this->getRuntimeProperties();

        return isset($runtimeProperties[$name]) || $this->reflectionClass->hasProperty($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultProperties()
    {
        return $this->reflectionClass->getDefaultProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function getFileName()
    {
        return $this->reflectionClass->getFileName();
    }

    /**
     * {@inheritdoc}
     */
    public function getLocatedSource()
    {
        return $this->reflectionClass->getLocatedSource();
    }

    /**
     * {@inheritdoc}
     */
    public function getStartLine()
    {
        return $this->reflectionClass->getStartLine();
    }

    /**
     * {@inheritdoc}
     */
    public function getEndLine()
    {
        return $this->reflectionClass->getEndLine();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentClass()
    {
        return $this->reflectionClass->getParentClass();
    }

    /**
     * {@inheritdoc}
     */
    public function getDocComment()
    {
        return $this->reflectionClass->getDocComment();
    }

    /**
     * {@inheritdoc}
     */
    public function isInternal()
    {
        return $this->reflectionClass->isInternal();
    }

    /**
     * {@inheritdoc}
     */
    public function isUserDefined()
    {
        return $this->reflectionClass->isUserDefined();
    }

    /**
     * {@inheritdoc}
     */
    public function isAbstract()
    {
        return $this->reflectionClass->isAbstract();
    }

    /**
     * {@inheritdoc}
     */
    public function isFinal()
    {
        return $this->reflectionClass->isFinal();
    }

    /**
     * {@inheritdoc}
     */
    public function getModifiers()
    {
        return $this->reflectionClass->getModifiers();
    }

    /**
     * {@inheritdoc}
     */
    public function isTrait()
    {
        return $this->reflectionClass->isTrait();
    }

    /**
     * {@inheritdoc}
     */
    public function isInterface()
    {
        return $this->reflectionClass->isInterface();
    }

    /**
     * {@inheritdoc}
     */
    public function getTraits()
    {
        return $this->reflectionClass->getTraits();
    }

    /**
     * {@inheritdoc}
     */
    public function getTraitNames()
    {
        return $this->reflectionClass->getTraitNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getTraitAliases()
    {
        return $this->reflectionClass->getTraitAliases();
    }

    /**
     * {@inheritdoc}
     */
    public function getInterfaces()
    {
        return $this->reflectionClass->getInterfaces();
    }

    /**
     * {@inheritdoc}
     */
    public function getImmediateInterfaces()
    {
        return $this->reflectionClass->getImmediateInterfaces();
    }

    /**
     * {@inheritdoc}
     */
    public function getInterfaceNames()
    {
        return $this->reflectionClass->getInterfaceNames();
    }

    /**
     * {@inheritdoc}
     */
    public function isInstance($object)
    {
        return $this->reflectionClass->isInstance($object);
    }

    /**
     * {@inheritdoc}
     */
    public function isSubclassOf($className)
    {
        return $this->reflectionClass->isSubclassOf($className);
    }

    /**
     * {@inheritdoc}
     */
    public function implementsInterface($interfaceName)
    {
        return $this->reflectionClass->implementsInterface($interfaceName);
    }

    /**
     * {@inheritdoc}
     */
    public function isInstantiable()
    {
        return $this->reflectionClass->isInstantiable();
    }

    /**
     * {@inheritdoc}
     */
    public function isCloneable()
    {
        return $this->reflectionClass->isCloneable();
    }

    /**
     * {@inheritdoc}
     */
    public function isIterateable()
    {
        return $this->reflectionClass->isIterateable();
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        throw Exception\Uncloneable::fromClass(__CLASS__);
    }

    /**
     * {@inheritdoc}
     */
    public function setStaticPropertyValue($propertyName, $value)
    {
        $this->reflectionClass->setStaticPropertyValue($propertyName, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticPropertyValue($propertyName)
    {
        return $this->reflectionClass->getStaticPropertyValue($propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function getAst()
    {
        return $this->reflectionClass->getAst();
    }

    /**
     * {@inheritdoc}
     */
    public function setFinal($isFinal)
    {
        return $this->reflectionClass->setFinal($isFinal);
    }

    /**
     * {@inheritdoc}
     */
    public function removeMethod($methodName)
    {
        return $this->reflectionClass->removeMethod($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function addMethod($methodName)
    {
        return $this->reflectionClass->addMethod($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function removeProperty($methodName)
    {
        return $this->reflectionClass->removeProperty($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function addProperty($methodName, $visibility = 'public', $static = false)
    {
        return $this->reflectionClass->addProperty($methodName, $visibility, $static);
    }
}
