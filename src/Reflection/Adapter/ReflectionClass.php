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
     * @return string
     */
    public static function export($argument, $return = false)
    {
        return BetterReflectionClass::export(...func_get_args());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->betterReflectionClass->__toString();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->betterReflectionClass->getName();
    }

    /**
     * @return bool
     */
    public function isInternal()
    {
        return $this->betterReflectionClass->isInternal();
    }

    /**
     * @return bool
     */
    public function isUserDefined()
    {
        return $this->betterReflectionClass->isUserDefined();
    }

    /**
     * @return bool
     */
    public function isInstantiable()
    {
        return $this->betterReflectionClass->isInstantiable();
    }

    /**
     * @return bool
     */
    public function isCloneable()
    {
        return $this->betterReflectionClass->isCloneable();
    }

    /**
     * @return null|string
     */
    public function getFileName()
    {
        return $this->betterReflectionClass->getFileName();
    }

    /**
     * @return int
     */
    public function getStartLine()
    {
        return $this->betterReflectionClass->getStartLine();
    }

    /**
     * @return int
     */
    public function getEndLine()
    {
        return $this->betterReflectionClass->getEndLine();
    }

    /**
     * @return string
     */
    public function getDocComment()
    {
        return $this->betterReflectionClass->getDocComment();
    }

    /**
     * @return ReflectionMethod
     */
    public function getConstructor()
    {
        return new ReflectionMethod($this->betterReflectionClass->getConstructor());
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasMethod($name)
    {
        return $this->betterReflectionClass->hasMethod($name);
    }

    /**
     * @param string $name
     * @return ReflectionMethod
     */
    public function getMethod($name)
    {
        return new ReflectionMethod($this->betterReflectionClass->getMethod($name));
    }

    /**
     * @return ReflectionMethod[]
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
     * @param string $name
     * @return bool
     */
    public function hasProperty($name)
    {
        return $this->betterReflectionClass->hasProperty($name);
    }

    /**
     * @param string $name
     * @return ReflectionProperty|null
     */
    public function getProperty($name)
    {
        return new ReflectionProperty($this->betterReflectionClass->getProperty($name));
    }

    /**
     * @return ReflectionProperty[]
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
     * @param string $name
     * @return bool
     */
    public function hasConstant($name)
    {
        return $this->betterReflectionClass->hasConstant($name);
    }

    /**
     * @return mixed[]
     */
    public function getConstants()
    {
        return $this->betterReflectionClass->getConstants();
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getConstant($name)
    {
        return $this->betterReflectionClass->getConstant($name);
    }

    /**
     * @return ReflectionClass[]
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
     * @return string[]
     */
    public function getInterfaceNames()
    {
        return $this->betterReflectionClass->getInterfaceNames();
    }

    /**
     * @return bool
     */
    public function isInterface()
    {
        return $this->betterReflectionClass->isInterface();
    }

    /**
     * @return ReflectionClass[]
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
     * @return string[]
     */
    public function getTraitNames()
    {
        return $this->betterReflectionClass->getTraitNames();
    }

    /**
     * @return string[]
     */
    public function getTraitAliases()
    {
        return $this->betterReflectionClass->getTraitAliases();
    }

    /**
     * @return bool
     */
    public function isTrait()
    {
        return $this->betterReflectionClass->isTrait();
    }

    /**
     * @return bool
     */
    public function isAbstract()
    {
        return $this->betterReflectionClass->isAbstract();
    }

    /**
     * @return bool
     */
    public function isFinal()
    {
        return $this->betterReflectionClass->isFinal();
    }

    /**
     * @return int
     */
    public function getModifiers()
    {
        return $this->betterReflectionClass->getModifiers();
    }

    /**
     * @param object $object
     * @return bool
     */
    public function isInstance($object)
    {
        return $this->betterReflectionClass->isInstance($object);
    }

    /**
     * @throws \Exception
     */
    public function newInstance($args = null, $_ = null)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function newInstanceWithoutConstructor()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function newInstanceArgs(array $args = null)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @return BetterReflectionClass
     */
    public function getParentClass()
    {
        return new ReflectionClass($this->betterReflectionClass->getParentClass());
    }

    /**
     * @param string $class
     * @return bool
     */
    public function isSubclassOf($class)
    {
        return $this->betterReflectionClass->isSubclassOf($class);
    }

    /**
     * @throws \Exception
     */
    public function getStaticProperties()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function getStaticPropertyValue($name, $default = null)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function setStaticPropertyValue($name, $value)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @return ReflectionProperty[]
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
     * @return bool
     */
    public function isIterateable()
    {
        return $this->betterReflectionClass->isIterateable();
    }

    /**
     * @param string $interface
     * @return bool
     */
    public function implementsInterface($interface)
    {
        return $this->betterReflectionClass->implementsInterface($interface);
    }

    /**
     * @throws \Exception
     */
    public function getExtension()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function getExtensionName()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @return bool
     */
    public function inNamespace()
    {
        return $this->betterReflectionClass->inNamespace();
    }

    /**
     * @return string
     */
    public function getNamespaceName()
    {
        return $this->betterReflectionClass->getNamespaceName();
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        return $this->betterReflectionClass->getShortName();
    }
}
