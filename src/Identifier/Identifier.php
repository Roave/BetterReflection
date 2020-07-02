<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Identifier;

use Roave\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;

use function ltrim;
use function preg_match;
use function strpos;

class Identifier
{
    public const WILDCARD = '*';

    private const VALID_NAME_REGEXP = '/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\\\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*/';

    private string $name;

    private IdentifierType $type;

    /**
     * @throws InvalidIdentifierName
     */
    public function __construct(string $name, IdentifierType $type)
    {
        $this->type = $type;

        if (
            $name === self::WILDCARD
            || $name === ReflectionFunctionAbstract::CLOSURE_NAME
            || strpos($name, ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX) === 0
        ) {
            $this->name = $name;

            return;
        }

        $name = ltrim($name, '\\');

        if (! preg_match(self::VALID_NAME_REGEXP, $name)) {
            throw InvalidIdentifierName::fromInvalidName($name);
        }

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): IdentifierType
    {
        return $this->type;
    }

    public function isClass(): bool
    {
        return $this->type->isClass();
    }

    public function isFunction(): bool
    {
        return $this->type->isFunction();
    }

    public function isConstant(): bool
    {
        return $this->type->isConstant();
    }
}
