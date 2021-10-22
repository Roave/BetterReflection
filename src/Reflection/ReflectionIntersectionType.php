<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\IntersectionType;

use function array_filter;
use function array_map;
use function implode;

class ReflectionIntersectionType extends ReflectionType
{
    /** @var list<ReflectionNamedType> */
    private array $types;

    public function __construct(IntersectionType $type, bool $allowsNull)
    {
        parent::__construct($allowsNull);
        $this->types = array_filter(
            array_map(static fn ($type): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType => ReflectionType::createFromTypeAndReflector($type), $type->types),
            static fn (ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType $type): bool => $type instanceof ReflectionNamedType,
        );
    }

    /**
     * @return list<ReflectionNamedType>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function __toString(): string
    {
        return implode('&', array_map(static fn (ReflectionNamedType $type): string => $type->__toString(), $this->types));
    }
}
