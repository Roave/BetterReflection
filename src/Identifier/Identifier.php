<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Identifier;

use Rector\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Reflection\ReflectionFunctionAbstract;

class Identifier
{
    public const WILDCARD = '*';

    private const VALID_NAME_REGEXP = '/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\\\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*/';

    /**
     * @var string
     */
    private $name;

    /**
     * @var IdentifierType
     */
    private $type;

    /**
     * @param string $name
     * @param IdentifierType $type
     *
     * @throws InvalidIdentifierName
     */
    public function __construct(string $name, IdentifierType $type)
    {
        $this->type = $type;

        if (self::WILDCARD === $name
            || ReflectionFunctionAbstract::CLOSURE_NAME === $name
            || 0 === \strpos($name, ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX)
        ) {
            $this->name = $name;
            return;
        }

        $name = \ltrim($name, '\\');

        if ( ! \preg_match(self::VALID_NAME_REGEXP, $name)) {
            throw InvalidIdentifierName::fromInvalidName($name);
        }

        $this->name = $name;
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
