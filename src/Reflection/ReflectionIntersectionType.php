<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node;
use PhpParser\Node\IntersectionType;
use Roave\BetterReflection\Reflector\Reflector;

use function array_map;
use function array_values;
use function assert;
use function implode;

class ReflectionIntersectionType extends ReflectionType
{
    /** @var list<ReflectionNamedType> */
    private array $types;

    public function __construct(
        private Reflector $reflector,
        private ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty $owner,
        IntersectionType $type,
    ) {
        $this->types = array_values(array_map(function (Node\Identifier|Node\Name $type): ReflectionNamedType {
            $type = ReflectionType::createFromNode($this->reflector, $this->owner, $type);
            assert($type instanceof ReflectionNamedType);

            return $type;
        }, $type->types));
    }

    /** @return list<ReflectionNamedType> */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function allowsNull(): bool
    {
        return false;
    }

    public function __toString(): string
    {
        return implode('&', array_map(static fn (ReflectionNamedType $type): string => $type->__toString(), $this->types));
    }
}
