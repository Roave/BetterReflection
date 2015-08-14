<?php

namespace BetterReflection\Reflection\Adapter;

use ReflectionParameter as CoreReflectionParameter;
use BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;

class ReflectionParameter extends CoreReflectionParameter
{
    /**
     * @var BetterReflectionParameter
     */
    private $betterReflectionParameter;

    public function __construct(BetterReflectionParameter $betterReflectionParameter)
    {
        $this->betterReflectionParameter = $betterReflectionParameter;
    }

    /**
     * @return string
     */
    public static function export()
    {
        return BetterReflectionParameter::export(...func_get_args());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->betterReflectionParameter->__toString();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->betterReflectionParameter->getName();
    }

    /**
     * @return bool
     */
    public function isPassedByReference()
    {
        return $this->betterReflectionParameter->isPassedByReference();
    }

    /**
     * @return bool
     */
    public function canBePassedByValue()
    {
        return $this->betterReflectionParameter->canBePassedByValue();
    }

    /**
     * @return ReflectionFunction|ReflectionMethod
     */
    public function getDeclaringFunction()
    {
        $function = $this->betterReflectionParameter->getDeclaringFunction();

        if ($function instanceof BetterReflectionMethod) {
            return new ReflectionMethod($function);
        }

        return new ReflectionFunction($function);
    }

    /**
     * @return ReflectionClass|null
     */
    public function getDeclaringClass()
    {
        $declaringClass = $this->betterReflectionParameter->getDeclaringClass();

        if (null === $declaringClass) {
            return null;
        }

        return new ReflectionClass($declaringClass);
    }

    /**
     * @return ReflectionClass|null
     */
    public function getClass()
    {
        $class = $this->betterReflectionParameter->getClass();

        if (null === $class) {
            return null;
        }

        return new ReflectionClass($class);
    }

    /**
     * @return bool
     */
    public function isArray()
    {
        return $this->betterReflectionParameter->isArray();
    }

    /**
     * @return bool
     */
    public function isCallable()
    {
        return $this->betterReflectionParameter->isCallable();
    }

    /**
     * @return bool
     */
    public function allowsNull()
    {
        return $this->betterReflectionParameter->allowsNull();
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->betterReflectionParameter->getPosition();
    }

    /**
     * @return bool
     */
    public function isOptional()
    {
        return $this->betterReflectionParameter->isOptional();
    }

    /**
     * @return bool
     */
    public function isDefaultValueAvailable()
    {
        return $this->betterReflectionParameter->isDefaultValueAvailable();
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->betterReflectionParameter->getDefaultValue();
    }

    /**
     * @return bool
     */
    public function isDefaultValueConstant()
    {
        return $this->betterReflectionParameter->isDefaultValueConstant();
    }

    /**
     * @return string
     */
    public function getDefaultValueConstantName()
    {
        return $this->betterReflectionParameter->getDefaultValueConstantName();
    }
}
