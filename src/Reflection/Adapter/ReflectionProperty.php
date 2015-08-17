<?php

namespace BetterReflection\Reflection\Adapter;

use ReflectionProperty as CoreReflectionProperty;
use BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;

class ReflectionProperty extends CoreReflectionProperty
{
    /**
     * @var BetterReflectionProperty
     */
    private $betterReflectionProperty;

    public function __construct(BetterReflectionProperty $betterReflectionProperty)
    {
        $this->betterReflectionProperty = $betterReflectionProperty;
    }

    /**
     * @return string
     */
    public static function export($class, $name, $return = null)
    {
        return BetterReflectionProperty::export(...func_get_args());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->betterReflectionProperty->__toString();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->betterReflectionProperty->getName();
    }

    /**
     * @throws \Exception
     */
    public function getValue($object = null)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function setValue($object, $value = null)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return $this->betterReflectionProperty->isPublic();
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return $this->betterReflectionProperty->isPrivate();
    }

    /**
     * @return bool
     */
    public function isProtected()
    {
        return $this->betterReflectionProperty->isProtected();
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return $this->betterReflectionProperty->isStatic();
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->betterReflectionProperty->isDefault();
    }

    /**
     * @return int
     */
    public function getModifiers()
    {
        return $this->betterReflectionProperty->getModifiers();
    }

    /**
     * @return ReflectionClass
     */
    public function getDeclaringClass()
    {
        return new ReflectionClass($this->betterReflectionProperty->getDeclaringClass());
    }

    /**
     * @return string
     */
    public function getDocComment()
    {
        return $this->betterReflectionProperty->getDocComment();
    }

    /**
     * @throws \Exception
     */
    public function setAccessible($visible)
    {
        throw new \Exception('Not implemented');
    }
}
