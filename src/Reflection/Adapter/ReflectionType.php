<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflection\Adapter;

use ReflectionType as CoreReflectionType;
use Rector\BetterReflection\Reflection\ReflectionType as BetterReflectionType;

class ReflectionType extends CoreReflectionType
{
    /**
     * @var BetterReflectionType
     */
    private $betterReflectionType;

    public function __construct(BetterReflectionType $betterReflectionType)
    {
        $this->betterReflectionType = $betterReflectionType;
    }

    public static function fromReturnTypeOrNull(?BetterReflectionType $betterReflectionType) : ?self
    {
        if (null === $betterReflectionType) {
            return null;
        }

        return new self($betterReflectionType);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString() : string
    {
        return $this->betterReflectionType->__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function allowsNull() : bool
    {
        return $this->betterReflectionType->allowsNull();
    }

    /**
     * {@inheritDoc}
     */
    public function isBuiltin() : bool
    {
        $type = (string) $this->betterReflectionType;

        if ('self' === $type || 'parent' === $type) {
            return false;
        }

        return $this->betterReflectionType->isBuiltin();
    }
}
