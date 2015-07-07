<?php

namespace BetterReflection\Identifier;

use PhpParser\Node;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\Reflection;

class IdentifierType
{
    const IDENTIFIER_CLASS = ReflectionClass::class;

    /**
     * @var string[]
     */
    private $validTypes = [
        self::IDENTIFIER_CLASS,
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
     * Check to see if a reflector is of a valid type specified by this identifier
     *
     * @param Reflection $reflector
     * @return bool
     */
    public function isMatchingReflector(Reflection $reflector)
    {
        if ($this->name == self::IDENTIFIER_CLASS) {
            return $reflector instanceof ReflectionClass;
        }

        return false;
    }
}
