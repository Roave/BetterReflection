<?php

namespace BetterReflection\Reflection;

use PhpParser\Node;

class Symbol
{
    const SYMBOL_CLASS = ReflectionClass::class;

    /**
     * @var string[]
     */
    private $validSymbols = [
        self::SYMBOL_CLASS,
    ];

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    public function __construct($name, $type = self::SYMBOL_CLASS)
    {
        if ('\\' == $name[0]) {
            $name = substr($name, 1);
        }
        $this->name = (string)$name;

        if (!in_array($type, $this->validSymbols)) {
            throw new \InvalidArgumentException(sprintf(
                '%s is not a valid symbol type',
                $type
            ));
        }
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getDisplayType()
    {
        return ucfirst($this->type);
    }

    /**
     * @todo implement this
     * @return bool
     */
    public function isLoaded()
    {
        return false;
    }

    public function isMatchingReflector(Reflection $reflector)
    {
        return $this->getType() == get_class($reflector);
    }
}
