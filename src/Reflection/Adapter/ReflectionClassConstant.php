<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use \ReflectionClassConstant as CoreReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;

class ReflectionClassConstant extends CoreReflectionClassConstant
{

    /**
     * @var BetterReflectionClassConstant
     */
    private $betterClassConstant;

    public function __construct(BetterReflectionClassConstant $betterClassConstant)
    {
        $this->betterClassConstant = $betterClassConstant;
    }

    /**
     * Get the name of the reflection (e.g. if this is a ReflectionClass this
     * will be the class name).
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->betterClassConstant->getName();
    }

    /**
     * Returns constant value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->betterClassConstant->getValue();
    }

    /**
     * Constant is public
     *
     * @return bool
     */
    public function isPublic() : bool
    {
        return $this->betterClassConstant->isPublic();
    }

    /**
     * Cosnstant is private
     *
     * @return bool
     */
    public function isPrivate() : bool
    {
        return $this->betterClassConstant->isPrivate();
    }

    /**
     * Constant is protected
     *
     * @return bool
     */
    public function isProtected() : bool
    {
        return $this->betterClassConstant->isProtected();
    }

    /**
     * Returns a bitfield of the access modifiers for this constant
     *
     * @return int
     */
    public function getModifiers() : int
    {
        return $this->betterClassConstant->getModifiers();
    }

    /**
     * Get the declaring class
     *
     * @return ReflectionClass
     */
    public function getDeclaringClass() : ReflectionClass
    {
        return new ReflectionClass($this->betterClassConstant->getDeclaringClass());
    }

    /**
     * Returns the doc comment for this constant
     *
     * @return string
     */
    public function getDocComment() : string
    {
        return $this->betterClassConstant->getDocComment();
    }

    /**
     * To string
     *
     * @link http://php.net/manual/en/reflector.tostring.php
     * @return string
     * @since 5.0
     */
    public function __toString()
    {
        return $this->betterClassConstant->__toString();
    }
}
