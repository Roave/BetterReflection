<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

use function array_key_exists;
use function strtolower;

class ReflectionNamedType extends ReflectionType
{
    private const BUILT_IN_TYPES = [
        'int'      => null,
        'float'    => null,
        'string'   => null,
        'bool'     => null,
        'callable' => null,
        'self'     => null,
        'parent'   => null,
        'array'    => null,
        'iterable' => null,
        'object'   => null,
        'void'     => null,
        'mixed'    => null,
        'static'   => null,
        'null'     => null,
    ];

    private string $name;

    /**
     * @param Identifier|Name $type
     */
    public function __construct($type, bool $allowsNull)
    {
        parent::__construct($allowsNull);
        $this->name = (string) $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Checks if it is a built-in type (i.e., it's not an object...)
     *
     * @see https://php.net/manual/en/reflectiontype.isbuiltin.php
     */
    public function isBuiltin(): bool
    {
        return array_key_exists(strtolower($this->name), self::BUILT_IN_TYPES);
    }

    public function __toString(): string
    {
        $name = '';
        if ($this->allowsNull()) {
            $name .= '?';
        }

        return $name . $this->getName();
    }
}
