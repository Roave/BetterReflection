<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionClassConstant as CoreReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;

class ReflectionClassConstant extends CoreReflectionClassConstant
{
    /** @var BetterReflectionClassConstant */
    private $betterClassConstant;

    public function __construct(BetterReflectionClassConstant $betterClassConstant)
    {
        $this->betterClassConstant = $betterClassConstant;
    }

    /**
     * Get the name of the reflection (e.g. if this is a ReflectionClass this
     * will be the class name).
     */
    public function getName() : string
    {
        return $this->betterClassConstant->getName();
    }

    /**
     * Returns constant value
     *
     * @return scalar|array<scalar>|null
     */
    public function getValue()
    {
        return $this->betterClassConstant->getValue();
    }

    /**
     * Constant is public
     */
    public function isPublic() : bool
    {
        return $this->betterClassConstant->isPublic();
    }

    /**
     * Constant is private
     */
    public function isPrivate() : bool
    {
        return $this->betterClassConstant->isPrivate();
    }

    /**
     * Constant is protected
     */
    public function isProtected() : bool
    {
        return $this->betterClassConstant->isProtected();
    }

    /**
     * Returns a bitfield of the access modifiers for this constant
     */
    public function getModifiers() : int
    {
        return $this->betterClassConstant->getModifiers();
    }

    /**
     * Get the declaring class
     */
    public function getDeclaringClass() : ReflectionClass
    {
        return new ReflectionClass($this->betterClassConstant->getDeclaringClass());
    }

    /**
     * Returns the doc comment for this constant
     *
     * @return string|false
     */
    public function getDocComment()
    {
        return $this->betterClassConstant->getDocComment() ?: false;
    }

    /**
     * To string
     *
     * @link https://php.net/manual/en/reflector.tostring.php
     */
    public function __toString() : string
    {
        return $this->betterClassConstant->__toString();
    }
}
