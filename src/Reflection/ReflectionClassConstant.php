<?php

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\Stmt\Class_;

class ReflectionClassConstant implements \Reflector
{
    /**
     * @var string Name of constant
     */
    private $name;

    /**
     * @var mixed Value of constant
     */
    private $value;

    /**
     * @var int Constant visibility flags
     */
    private $flags;

    private function __construct()
    {
    }

    /**
     * Create a reflection of a class's constant by it's flags, name and value
     *
     * @param int $flags
     * @param string $name
     * @param mixed $value
     * @return ReflectionClassConstant
     */
    public static function createFromConstFlagsNameAndValue(int $flags, string $name, $value) : self
    {
        $ref = new self();
        $ref->flags = $flags === 0 ? Class_::MODIFIER_PUBLIC : $flags;
        $ref->name = $name;
        $ref->value = $value;
        return $ref;
    }

    /**
     * Get the name of the reflection (e.g. if this is a ReflectionClass this
     * will be the class name).
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Returns constant value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Constant is public
     *
     * @return bool
     */
    public function isPublic() : bool
    {
        return (bool)($this->flags & Class_::MODIFIER_PUBLIC);
    }

    /**
     * Cosnstant is private
     *
     * @return bool
     */
    public function isPrivate() : bool
    {
        return (bool)($this->flags & Class_::MODIFIER_PRIVATE);
    }

    /**
     * Constant is protected
     *
     * @return bool
     */
    public function isProtected() : bool
    {
        return (bool)($this->flags & Class_::MODIFIER_PROTECTED);
    }

    private function getVisibility()
    {
        if ($this->isPublic()) {
            return 'public';
        }
        if ($this->isPrivate()) {
            return 'private';
        }
        if ($this->isProtected()) {
            return 'protected';
        }
        return '';
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
        return sprintf(
            'Constant [ %s %s ] { %s }' . PHP_EOL,
            $this->getVisibility(),
            $this->getName(),
            $this->getValue()
        );
    }

    /**
     * Exports
     *
     * @link http://php.net/manual/en/reflector.export.php
     * @return string
     * @since 5.0
     */
    public static function export()
    {
        throw new \Exception('Unable to export statically');
    }
}
