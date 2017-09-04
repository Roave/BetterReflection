<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionException as CoreReflectionException;
use ReflectionObject as CoreReflectionObject;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionObject as BetterReflectionObject;

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
     * {@inheritDoc}
     */
    public static function export($argument, $return = null)
    {
        return BetterReflectionObject::export(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->betterReflectionObject->__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->betterReflectionObject->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function isInternal()
    {
        return $this->betterReflectionObject->isInternal();
    }

    /**
     * {@inheritDoc}
     */
    public function isUserDefined()
    {
        return $this->betterReflectionObject->isUserDefined();
    }

    /**
     * {@inheritDoc}
     */
    public function isInstantiable()
    {
        return $this->betterReflectionObject->isInstantiable();
    }

    /**
     * {@inheritDoc}
     */
    public function isCloneable()
    {
        return $this->betterReflectionObject->isCloneable();
    }

    /**
     * {@inheritDoc}
     */
    public function getFileName()
    {
        return $this->betterReflectionObject->getFileName() ?? false;
    }

    /**
     * {@inheritDoc}
     */
    public function getStartLine()
    {
        return $this->betterReflectionObject->getStartLine();
    }

    /**
     * {@inheritDoc}
     */
    public function getEndLine()
    {
        return $this->betterReflectionObject->getEndLine();
    }

    /**
     * {@inheritDoc}
     */
    public function getDocComment()
    {
        return $this->betterReflectionObject->getDocComment() ?: false;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructor()
    {
        return new ReflectionMethod($this->betterReflectionObject->getConstructor());
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethod($name)
    {
        return $this->betterReflectionObject->hasMethod($this->getMethodRealName($name));
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod($name)
    {
        return new ReflectionMethod($this->betterReflectionObject->getMethod($this->getMethodRealName($name)));
    }

    private function getMethodRealName(string $name) : string
    {
        $realMethodNames = \array_map(function (BetterReflectionMethod $method) : string {
            return $method->getName();
        }, $this->betterReflectionObject->getMethods());

        $methodNames = \array_combine(\array_map(function (string $methodName) : string {
            return \strtolower($methodName);
        }, $realMethodNames), $realMethodNames);

        return $methodNames[\strtolower($name)] ?? $name;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function hasProperty($name)
    {
        return $this->betterReflectionObject->hasProperty($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty($name)
    {
        $property = $this->betterReflectionObject->getProperty($name);

        if (null === $property) {
            return null;
        }

        return new ReflectionProperty($property);
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function hasConstant($name)
    {
        return $this->betterReflectionObject->hasConstant($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getConstants()
    {
        return $this->betterReflectionObject->getConstants();
    }

    /**
     * {@inheritDoc}
     */
    public function getConstant($name)
    {
        return $this->betterReflectionObject->getConstant($name);
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function getInterfaceNames()
    {
        return $this->betterReflectionObject->getInterfaceNames();
    }

    /**
     * {@inheritDoc}
     */
    public function isInterface()
    {
        return $this->betterReflectionObject->isInterface();
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function getTraitNames()
    {
        return $this->betterReflectionObject->getTraitNames();
    }

    /**
     * {@inheritDoc}
     */
    public function getTraitAliases()
    {
        return $this->betterReflectionObject->getTraitAliases();
    }

    /**
     * {@inheritDoc}
     */
    public function isTrait()
    {
        return $this->betterReflectionObject->isTrait();
    }

    /**
     * {@inheritDoc}
     */
    public function isAbstract()
    {
        return $this->betterReflectionObject->isAbstract();
    }

    /**
     * {@inheritDoc}
     */
    public function isFinal()
    {
        return $this->betterReflectionObject->isFinal();
    }

    /**
     * {@inheritDoc}
     */
    public function getModifiers()
    {
        return $this->betterReflectionObject->getModifiers();
    }

    /**
     * {@inheritDoc}
     */
    public function isInstance($object)
    {
        return $this->betterReflectionObject->isInstance($object);
    }

    /**
     * {@inheritDoc}
     */
    public function newInstance($arg = null, ...$args)
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
    public function newInstanceArgs(?array $args = null)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getParentClass()
    {
        $parentClass = $this->betterReflectionObject->getParentClass();

        if (null === $parentClass) {
            return false;
        }

        return new ReflectionClass($parentClass);
    }
    /**
     * {@inheritDoc}
     */
    public function isSubclassOf($class)
    {
        $realParentClassNames = $this->betterReflectionObject->getParentClassNames();

        $parentClassNames = \array_combine(\array_map(function (string $parentClassName) : string {
            return \strtolower($parentClassName);
        }, $realParentClassNames), $realParentClassNames);

        $realParentClassName = $parentClassNames[\strtolower($class)] ?? $class;

        return $this->betterReflectionObject->isSubclassOf($realParentClassName);
    }

    /**
     * {@inheritDoc}
     */
    public function getStaticProperties()
    {
        return $this->betterReflectionObject->getStaticProperties();
    }

    /**
     * {@inheritDoc}
     */
    public function getStaticPropertyValue($name, $default = null)
    {
        $property = $this->getProperty($name);

        if (null === $property) {
            if (2 === \func_num_args()) {
                return $default;
            }

            throw new CoreReflectionException(\sprintf('Property "%s" does not exist', $name));
        }

        if ( ! $property->isAccessible()) {
            throw new CoreReflectionException(\sprintf('Property "%s" is not accessible', $name));
        }

        if ( ! $property->isStatic()) {
            throw new CoreReflectionException(\sprintf('Property "%s" is not static', $name));
        }

        return $property->getValue();
    }

    /**
     * {@inheritDoc}
     */
    public function setStaticPropertyValue($name, $value)
    {
        $property = $this->getProperty($name);

        if (null === $property) {
            throw new CoreReflectionException(\sprintf('Property "%s" does not exist', $name));
        }

        if ( ! $property->isAccessible()) {
            throw new CoreReflectionException(\sprintf('Property "%s" is not accessible', $name));
        }

        if ( ! $property->isStatic()) {
            throw new CoreReflectionException(\sprintf('Property "%s" is not static', $name));
        }

        $property->setValue($value);
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultProperties()
    {
        return $this->betterReflectionObject->getDefaultProperties();
    }

    /**
     * {@inheritDoc}
     */
    public function isIterateable()
    {
        return $this->betterReflectionObject->isIterateable();
    }

    /**
     * {@inheritDoc}
     */
    public function implementsInterface($interface)
    {
        $realInterfaceNames = $this->betterReflectionObject->getInterfaceNames();

        $interfaceNames = \array_combine(\array_map(function (string $interfaceName) : string {
            return \strtolower($interfaceName);
        }, $realInterfaceNames), $realInterfaceNames);

        $realInterfaceName = $interfaceNames[\strtolower($interface)] ?? $interface;

        return $this->betterReflectionObject->implementsInterface($realInterfaceName);
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
        return $this->betterReflectionObject->getExtensionName() ?? false;
    }

    /**
     * {@inheritDoc}
     */
    public function inNamespace()
    {
        return $this->betterReflectionObject->inNamespace();
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespaceName()
    {
        return $this->betterReflectionObject->getNamespaceName();
    }

    /**
     * {@inheritDoc}
     */
    public function getShortName()
    {
        return $this->betterReflectionObject->getShortName();
    }
}
