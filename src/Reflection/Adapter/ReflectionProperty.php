<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;

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
     * {@inheritDoc}
     */
    public static function export($class, $name, $return = null)
    {
        return BetterReflectionProperty::export(...func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->betterReflectionProperty->__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->betterReflectionProperty->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getValue($object = null)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function setValue($object, $value = null)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isPublic()
    {
        return $this->betterReflectionProperty->isPublic();
    }

    /**
     * {@inheritDoc}
     */
    public function isPrivate()
    {
        return $this->betterReflectionProperty->isPrivate();
    }

    /**
     * {@inheritDoc}
     */
    public function isProtected()
    {
        return $this->betterReflectionProperty->isProtected();
    }

    /**
     * {@inheritDoc}
     */
    public function isStatic()
    {
        return $this->betterReflectionProperty->isStatic();
    }

    /**
     * {@inheritDoc}
     */
    public function isDefault()
    {
        return $this->betterReflectionProperty->isDefault();
    }

    /**
     * {@inheritDoc}
     */
    public function getModifiers()
    {
        return $this->betterReflectionProperty->getModifiers();
    }

    /**
     * {@inheritDoc}
     */
    public function getDeclaringClass()
    {
        return new ReflectionClass($this->betterReflectionProperty->getDeclaringClass());
    }

    /**
     * {@inheritDoc}
     */
    public function getDocComment()
    {
        return $this->betterReflectionProperty->getDocComment() ?: false;
    }

    /**
     * {@inheritDoc}
     */
    public function setAccessible($visible)
    {
        throw new Exception\NotImplemented('Not implemented');
    }
}
