<?php

namespace BetterReflection\Reflection\Adapter;

use ReflectionObject as CoreReflectionObject;
use BetterReflection\Reflection\ReflectionObject as BetterReflectionObject;

class ReflectionObject extends CoreReflectionObject
{
    /**
     * @var BetterReflectionObject
     */
    private $betterReflectionObject;

    public function __construct(BetterReflectionObject $betterReflectionObject)
    {
        $this->betterReflectionObject = $betterReflectionObject;
    }

    /**
     * @return string
     */
    public static function export($argument, $return = null)
    {
        return BetterReflectionObject::export(...func_get_args());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->betterReflectionObject->__toString();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->betterReflectionObject->getName();
    }

    /**
     * @return bool
     */
    public function isInternal()
    {
        return $this->betterReflectionObject->isInternal();
    }

    /**
     * @return bool
     */
    public function isUserDefined()
    {
        return $this->betterReflectionObject->isUserDefined();
    }

    /**
     * @return bool
     */
    public function isInstantiable()
    {
        return $this->betterReflectionObject->isInstantiable();
    }

    /**
     * @return bool
     */
    public function isCloneable()
    {
        return $this->betterReflectionObject->isCloneable();
    }

    /**
     * @return null|string
     */
    public function getFileName()
    {
        return $this->betterReflectionObject->getFileName();
    }

    /**
     * @return int
     */
    public function getStartLine()
    {
        return $this->betterReflectionObject->getStartLine();
    }

    /**
     * @return int
     */
    public function getEndLine()
    {
        return $this->betterReflectionObject->getEndLine();
    }

    /**
     * @return int
     */
    public function getDocComment()
    {
        return $this->betterReflectionObject->getDocComment();
    }

    /**
     * @return ReflectionMethod
     */
    public function getConstructor()
    {
        return new ReflectionMethod($this->betterReflectionObject->getConstructor());
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasMethod($name)
    {
        return $this->betterReflectionObject->hasMethod($name);
    }

    /**
     * @param string $name
     * @return ReflectionMethod
     */
    public function getMethod($name)
    {
        return new ReflectionMethod($this->betterReflectionObject->getMethod($name));
    }

    /**
     * @return ReflectionMethod[]
     */
    public function getMethods($filter = null)
    {
        $methods = $this->betterReflectionObject->getMethods();

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
        return $this->betterReflectionObject->hasProperty($name);
    }

    /**
     * @param string $name
     * @return ReflectionProperty|null
     */
    public function getProperty($name)
    {
        return new ReflectionProperty($this->betterReflectionObject->getProperty($name));
    }

    /**
     * @return ReflectionProperty[]
     */
    public function getProperties($filter = null)
    {
        $properties = $this->betterReflectionObject->getProperties();

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
        return $this->betterReflectionObject->hasConstant($name);
    }

    /**
     * @return mixed[]
     */
    public function getConstants()
    {
        return $this->betterReflectionObject->getConstants();
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getConstant($name)
    {
        return $this->betterReflectionObject->getConstant($name);
    }

    /**
     * @return ReflectionClass[]
     */
    public function getInterfaces()
    {
        $interfaces = $this->betterReflectionObject->getInterfaces();

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
        return $this->betterReflectionObject->getInterfaceNames();
    }

    /**
     * @return bool
     */
    public function isInterface()
    {
        return $this->betterReflectionObject->isInterface();
    }

    /**
     * @return ReflectionClass[]
     */
    public function getTraits()
    {
        $traits = $this->betterReflectionObject->getTraits();

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
        return $this->betterReflectionObject->getTraitNames();
    }

    /**
     * @return string[]
     */
    public function getTraitAliases()
    {
        return $this->betterReflectionObject->getTraitAliases();
    }

    /**
     * @return bool
     */
    public function isTrait()
    {
        return $this->betterReflectionObject->isTrait();
    }

    /**
     * @return bool
     */
    public function isAbstract()
    {
        return $this->betterReflectionObject->isAbstract();
    }

    /**
     * @return bool
     */
    public function isFinal()
    {
        return $this->betterReflectionObject->isFinal();
    }

    /**
     * @return int
     */
    public function getModifiers()
    {
        return $this->betterReflectionObject->getModifiers();
    }

    /**
     * @param object $object
     * @return bool
     */
    public function isInstance($object)
    {
        return $this->betterReflectionObject->isInstance($object);
    }

    /**
     * @throws \Exception
     */
    public function newInstance($args)
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
     * @return BetterReflectionObject
     */
    public function getParentClass()
    {
        return new ReflectionClass($this->betterReflectionObject->getParentClass());
    }

    /**
     * @param string $class
     * @return bool
     */
    public function isSubclassOf($class)
    {
        return $this->betterReflectionObject->isSubclassOf($class);
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
        $properties = $this->betterReflectionObject->getDefaultProperties();

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
        return $this->betterReflectionObject->isIterateable();
    }

    /**
     * @param string $interface
     * @return bool
     */
    public function implementsInterface($interface)
    {
        return $this->betterReflectionObject->implementsInterface($interface);
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
        return $this->betterReflectionObject->inNamespace();
    }

    /**
     * @return string
     */
    public function getNamespaceName()
    {
        return $this->betterReflectionObject->getNamespaceName();
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        return $this->betterReflectionObject->getShortName();
    }
}
