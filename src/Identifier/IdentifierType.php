<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Identifier;

use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;

class IdentifierType
{
    public const IDENTIFIER_CLASS    = ReflectionClass::class;
    public const IDENTIFIER_FUNCTION = ReflectionFunction::class;

    private const VALID_TYPES = [
        self::IDENTIFIER_CLASS    => null,
        self::IDENTIFIER_FUNCTION => null,
    ];

    /**
     * @var string
     */
    private $name;

    public function __construct($type = self::IDENTIFIER_CLASS)
    {
        if ( ! \array_key_exists($type, self::VALID_TYPES)) {
            throw new \InvalidArgumentException(\sprintf(
                '%s is not a valid identifier type',
                $type
            ));
        }
        $this->name = $type;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function isClass() : bool
    {
        return $this->name === self::IDENTIFIER_CLASS;
    }

    public function isFunction() : bool
    {
        return $this->name === self::IDENTIFIER_FUNCTION;
    }

    /**
     * Check to see if a reflector is of a valid type specified by this identifier.
     *
     * @param Reflection $reflector
     * @return bool
     */
    public function isMatchingReflector(Reflection $reflector) : bool
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
