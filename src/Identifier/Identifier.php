<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Identifier;

class Identifier
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var IdentifierType
     */
    private $type;

    public function __construct(string $name, IdentifierType $type)
    {
        $this->type = $type;

        $name = \ltrim($name, '\\');
        // @todo validate the name somehow (see issue #20)
        $this->name = (string) $name;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getType() : IdentifierType
    {
        return $this->type;
    }

    public function isClass() : bool
    {
        return $this->type->isClass();
    }

    public function isFunction() : bool
    {
        return $this->type->isFunction();
    }
}
