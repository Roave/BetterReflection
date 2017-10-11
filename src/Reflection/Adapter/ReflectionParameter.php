<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflection\Adapter;

use ReflectionParameter as CoreReflectionParameter;
use Rector\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Rector\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;

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
     * {@inheritDoc}
     */
    public static function export($function, $parameter, $return = null)
    {
        BetterReflectionParameter::export(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->betterReflectionParameter->__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->betterReflectionParameter->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function isPassedByReference()
    {
        return $this->betterReflectionParameter->isPassedByReference();
    }

    /**
     * {@inheritDoc}
     */
    public function canBePassedByValue()
    {
        return $this->betterReflectionParameter->canBePassedByValue();
    }

    /**
     * {@inheritDoc}
     */
    public function getDeclaringFunction()
    {
        /** @var BetterReflectionMethod|\Rector\BetterReflection\Reflection\ReflectionFunction $function */
        $function = $this->betterReflectionParameter->getDeclaringFunction();

        if ($function instanceof BetterReflectionMethod) {
            return new ReflectionMethod($function);
        }

        return new ReflectionFunction($function);
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function isArray()
    {
        return $this->betterReflectionParameter->isArray();
    }

    /**
     * {@inheritDoc}
     */
    public function isCallable()
    {
        return $this->betterReflectionParameter->isCallable();
    }

    /**
     * {@inheritDoc}
     */
    public function allowsNull()
    {
        return $this->betterReflectionParameter->allowsNull();
    }

    /**
     * {@inheritDoc}
     */
    public function getPosition()
    {
        return $this->betterReflectionParameter->getPosition();
    }

    /**
     * {@inheritDoc}
     */
    public function isOptional()
    {
        return $this->betterReflectionParameter->isOptional();
    }

    /**
     * {@inheritDoc}
     */
    public function isVariadic()
    {
        return $this->betterReflectionParameter->isVariadic();
    }

    /**
     * {@inheritDoc}
     */
    public function isDefaultValueAvailable()
    {
        return $this->betterReflectionParameter->isDefaultValueAvailable();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultValue()
    {
        return $this->betterReflectionParameter->getDefaultValue();
    }

    /**
     * {@inheritDoc}
     */
    public function isDefaultValueConstant()
    {
        return $this->betterReflectionParameter->isDefaultValueConstant();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultValueConstantName()
    {
        return $this->betterReflectionParameter->getDefaultValueConstantName();
    }

    /**
     * {@inheritDoc}
     */
    public function hasType()
    {
        return $this->betterReflectionParameter->hasType();
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return ReflectionType::fromReturnTypeOrNull($this->betterReflectionParameter->getType());
    }
}
