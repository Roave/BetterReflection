<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\UnionType;
use Roave\BetterReflection\Reflector\Reflector;

use function array_filter;
use function array_map;
use function implode;

class ReflectionUnionType extends ReflectionType
{
    /** @var list<ReflectionNamedType> */
    private array $types;

    public function __construct(
        Reflector $reflector,
        ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty $owner,
        UnionType $type,
        bool $allowsNull,
    ) {
        parent::__construct($reflector, $owner, $allowsNull);

        $this->types = array_filter(
            array_map(static fn ($type): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType => ReflectionType::createFromNode($reflector, $owner, $type), $type->types),
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
        return implode('|', array_map(static fn (ReflectionType $type): string => $type->__toString(), $this->types));
    }
}
