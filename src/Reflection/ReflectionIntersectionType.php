<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node;
use PhpParser\Node\IntersectionType;
use Roave\BetterReflection\Reflector\Reflector;

use function array_map;
use function assert;
use function implode;

/** @psalm-immutable */
final class ReflectionIntersectionType extends ReflectionType
{
    /** @var non-empty-list<ReflectionNamedType> */
    private array $types;

    /** @internal */
    public function __construct(
        Reflector $reflector,
        ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty|ReflectionClassConstant $owner,
        IntersectionType $type,
    ) {
        /** @var non-empty-list<ReflectionNamedType> $types */
        $types = array_map(static function (Node\Identifier|Node\Name $type) use ($reflector, $owner): ReflectionNamedType {
            $type = ReflectionType::createFromNode($reflector, $owner, $type);
            assert($type instanceof ReflectionNamedType);

            return $type;
        }, $type->types);

        $this->types = $types;
    }

    /** @internal */
    public function withOwner(ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty|ReflectionClassConstant $owner): static
    {
        $clone = clone $this;

        foreach ($clone->types as $typeNo => $innerType) {
            $clone->types[$typeNo] = $innerType->withOwner($owner);
        }

        return $clone;
    }

    /** @return non-empty-list<ReflectionNamedType> */
    public function getTypes(): array
    {
        return $this->types;
    }

    /** @return false */
    public function allowsNull(): bool
    {
        return false;
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        // @infection-ignore-all UnwrapArrayMap: It works without array_map() as well but this is less magical
        return implode('&', array_map(static fn (ReflectionNamedType $type): string => $type->__toString(), $this->types));
    }
}
