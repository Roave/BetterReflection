<?php

namespace BetterReflection\Reflection;

use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\EvaledCodeSourceLocator;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\Builder\Property as PropertyNodeBuilder;

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

    private function __construct(ReflectionClass $reflectionClass, $object)
    {
        $this->reflectionClass = $reflectionClass;
        $this->object = $object;
    }

    /**
     * Cannot instantiate this way, use ReflectionObject::createFromInstance
     *
     * @throws \LogicException
     */
    public static function createFromNode()
    {
        throw new \LogicException('Cannot create a ReflectionObject from node - use ReflectionObject::createFromInstance');
    }

    /**
     * Cannot instantiate this way, use ReflectionObject::createFromInstance
     *
     * @throws \LogicException
     */
    public static function createFromName()
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

        $reflectionClass = (new ClassReflector(new EvaledCodeSourceLocator()))
            ->reflect(get_class($object));

        return new self($reflectionClass, $object);
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
     * Note that we don't copy across Docblock, protected, private or static
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
}
