<?php

namespace BetterReflection\Reflection\Adapter;

use ReflectionClass as CoreReflectionClass;
use BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;

class ReflectionClass extends CoreReflectionClass
{
    /**
     * @var BetterReflectionClass
     */
    private $betterReflectionClass;

    public function __construct(BetterReflectionClass $betterReflectionClass)
    {
        $this->betterReflectionClass = $betterReflectionClass;
    }

    /**
     * {@inheritDoc}
     */
    public static function export($argument, $return = false)
    {
        return BetterReflectionClass::export(...func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->betterReflectionClass->__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->betterReflectionClass->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function isInternal()
    {
        return $this->betterReflectionClass->isInternal();
    }

    /**
     * {@inheritDoc}
     */
    public function isUserDefined()
    {
        return $this->betterReflectionClass->isUserDefined();
    }

    /**
     * {@inheritDoc}
     */
    public function isInstantiable()
    {
        return $this->betterReflectionClass->isInstantiable();
    }

    /**
     * {@inheritDoc}
     */
    public function isCloneable()
    {
        return $this->betterReflectionClass->isCloneable();
    }

    /**
     * {@inheritDoc}
     */
    public function getFileName()
    {
        return $this->betterReflectionClass->getFileName();
    }

    /**
     * {@inheritDoc}
     */
    public function getStartLine()
    {
        return $this->betterReflectionClass->getStartLine();
    }

    /**
     * {@inheritDoc}
     */
    public function getEndLine()
    {
        return $this->betterReflectionClass->getEndLine();
    }

    /**
     * {@inheritDoc}
     */
    public function getDocComment()
    {
        return $this->betterReflectionClass->getDocComment();
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructor()
    {
        return new ReflectionMethod($this->betterReflectionClass->getConstructor());
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethod($name)
    {
        return $this->betterReflectionClass->hasMethod($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod($name)
    {
        return new ReflectionMethod($this->betterReflectionClass->getMethod($name));
    }

    /**
     * {@inheritDoc}
     */
    public function getMethods($filter = null)
    {
        $methods = $this->betterReflectionClass->getMethods();

        $wrappedMethods = [];
        foreach ($methods as $key => $method) {
            $wrappedMethods[$key] = new ReflectionMethod($method);
        }
        return $wrappedMethods;
    }

    /**
     * {@inheritDoc}
     */
    public function hasProperty($name)
    {
        return $this->betterReflectionClass->hasProperty($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty($name)
    {
        return new ReflectionProperty($this->betterReflectionClass->getProperty($name));
    }

    /**
     * {@inheritDoc}
     */
    public function getProperties($filter = null)
    {
        $properties = $this->betterReflectionClass->getProperties();

        $wrappedProperties = [];
        foreach ($properties as $key => $property) {
            $wrappedProperties[$key] = new ReflectionProperty($property);
        }
        return $wrappedProperties;
    }

    /**
     * {@inheritDoc}
     */
    public function hasConstant($name)
    {
        return $this->betterReflectionClass->hasConstant($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getConstants()
    {
        return $this->betterReflectionClass->getConstants();
    }

    /**
     * {@inheritDoc}
     */
    public function getConstant($name)
    {
        return $this->betterReflectionClass->getConstant($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getInterfaces()
    {
        $interfaces = $this->betterReflectionClass->getInterfaces();

        $wrappedInterfaces = [];
        foreach ($interfaces as $key => $interface) {
            $wrappedInterfaces[$key] = new ReflectionClass($interface);
        }
        return $wrappedInterfaces;
    }

    /**
     * {@inheritDoc}
     */
    public function getInterfaceNames()
    {
        return $this->betterReflectionClass->getInterfaceNames();
    }

    /**
     * {@inheritDoc}
     */
    public function isInterface()
    {
        return $this->betterReflectionClass->isInterface();
    }

    /**
     * {@inheritDoc}
     */
    public function getTraits()
    {
        $traits = $this->betterReflectionClass->getTraits();

        $wrappedTraits = [];
        foreach ($traits as $key => $trait) {
            $wrappedTraits[$key] = new ReflectionClass($trait);
        }
        return $wrappedTraits;
    }

    /**
     * {@inheritDoc}
     */
    public function getTraitNames()
    {
        return $this->betterReflectionClass->getTraitNames();
    }

    /**
     * {@inheritDoc}
     */
    public function getTraitAliases()
    {
        return $this->betterReflectionClass->getTraitAliases();
    }

    /**
     * {@inheritDoc}
     */
    public function isTrait()
    {
        return $this->betterReflectionClass->isTrait();
    }

    /**
     * {@inheritDoc}
     */
    public function isAbstract()
    {
        return $this->betterReflectionClass->isAbstract();
    }

    /**
     * {@inheritDoc}
     */
    public function isFinal()
    {
        return $this->betterReflectionClass->isFinal();
    }

    /**
     * {@inheritDoc}
     */
    public function getModifiers()
    {
        return $this->betterReflectionClass->getModifiers();
    }

    /**
     * {@inheritDoc}
     */
    public function isInstance($object)
    {
        return $this->betterReflectionClass->isInstance($object);
    }

    /**
     * {@inheritDoc}
     */
    public function newInstance(...$args)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function newInstanceWithoutConstructor()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function newInstanceArgs(array $args = null)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getParentClass()
    {
        return new ReflectionClass($this->betterReflectionClass->getParentClass());
    }

    /**
     * {@inheritDoc}
     */
    public function isSubclassOf($class)
    {
        return $this->betterReflectionClass->isSubclassOf($class);
    }

    /**
     * {@inheritDoc}
     */
    public function getStaticProperties()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getStaticPropertyValue($name, $default = null)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function setStaticPropertyValue($name, $value)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultProperties()
    {
        $properties = $this->betterReflectionClass->getDefaultProperties();

        $wrappedProperties = [];
        foreach ($properties as $key => $property) {
            $wrappedProperties[$key] = new ReflectionProperty($property);
        }
        return $wrappedProperties;
    }

    /**
     * {@inheritDoc}
     */
    public function isIterateable()
    {
        return $this->betterReflectionClass->isIterateable();
    }

    /**
     * {@inheritDoc}
     */
    public function implementsInterface($interface)
    {
        return $this->betterReflectionClass->implementsInterface($interface);
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getExtensionName()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function inNamespace()
    {
        return $this->betterReflectionClass->inNamespace();
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespaceName()
    {
        return $this->betterReflectionClass->getNamespaceName();
    }

    /**
     * {@inheritDoc}
     */
    public function getShortName()
    {
        return $this->betterReflectionClass->getShortName();
    }
}
