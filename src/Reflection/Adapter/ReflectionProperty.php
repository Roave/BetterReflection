<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use Exception;
use ReflectionException as CoreReflectionException;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Throwable;

class ReflectionProperty extends CoreReflectionProperty
{
    /** @var BetterReflectionProperty */
    private $betterReflectionProperty;

    /** @var bool */
    private $accessible = false;

    public function __construct(BetterReflectionProperty $betterReflectionProperty)
    {
        $this->betterReflectionProperty = $betterReflectionProperty;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public static function export($class, $name, $return = null)
    {
        throw new Exception('Unable to export statically');
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
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Property not accessible');
        }

        try {
            return $this->betterReflectionProperty->getValue($object);
        } catch (NoObjectProvided | NotAnObject $e) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setValue($object, $value = null)
    {
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Property not accessible');
        }

        try {
            $this->betterReflectionProperty->setValue($object, $value);
        } catch (NoObjectProvided | NotAnObject $e) {
            return;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hasType()
    {
        return $this->betterReflectionProperty->hasType();
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return ReflectionNamedType::fromReturnTypeOrNull($this->betterReflectionProperty->getType());
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
        return new ReflectionClass($this->betterReflectionProperty->getImplementingClass());
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
    public function setAccessible($accessible)
    {
        $this->accessible = true;
    }

    public function isAccessible() : bool
    {
        return $this->accessible || $this->isPublic();
    }
}
