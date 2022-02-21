<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node;
use PhpParser\Node\IntersectionType;
use Roave\BetterReflection\Reflector\Reflector;

use function array_filter;
use function array_map;
use function array_values;
use function implode;

class ReflectionIntersectionType extends ReflectionType
{
    /** @var list<ReflectionNamedType> */
    private array $types;

    public function __construct(
        Reflector $reflector,
        ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty $owner,
        IntersectionType $type,
    ) {
        parent::__construct($reflector, $owner);

        $this->types = array_values(array_filter(
            array_map(static fn (Node\Identifier|Node\Name $type): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType => ReflectionType::createFromNode($reflector, $owner, $type), $type->types),
            static fn (ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType $type): bool => $type instanceof ReflectionNamedType,
        ));
    }

    /**
     * @return list<ReflectionNamedType>
     */
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
