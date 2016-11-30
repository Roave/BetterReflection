<?php

namespace Roave\BetterReflection\Identifier;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\Reflection;

class IdentifierType
{
    const IDENTIFIER_CLASS = ReflectionClass::class;
    const IDENTIFIER_FUNCTION = ReflectionFunction::class;

    /**
     * @var string[]
     */
    private $validTypes = [
        self::IDENTIFIER_CLASS,
        self::IDENTIFIER_FUNCTION,
    ];

    /**
     * @var string
     */
    private $name;

    public function __construct($type = self::IDENTIFIER_CLASS)
    {
        if (!in_array($type, $this->validTypes, true)) {
            throw new \InvalidArgumentException(sprintf(
                '%s is not a valid identifier type',
                $type
            ));
        }
        $this->name = $type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isClass()
    {
        return $this->name === self::IDENTIFIER_CLASS;
    }

    /**
     * @return bool
     */
    public function isFunction()
    {
        return $this->name === self::IDENTIFIER_FUNCTION;
    }

    /**
     * Check to see if a reflector is of a valid type specified by this identifier.
     *
     * @param Reflection $reflector
     * @return bool
     */
    public function isMatchingReflector(Reflection $reflector)
    {
        if ($this->name === self::IDENTIFIER_CLASS) {
            return $reflector instanceof ReflectionClass;
        }

        if ($this->name === self::IDENTIFIER_FUNCTION) {
            return $reflector instanceof ReflectionFunction;
        }

        return false;
    }
}
