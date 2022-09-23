<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\UnionType;
use Roave\BetterReflection\Reflector\Reflector;

use function array_map;
use function assert;
use function implode;
use function sprintf;

class ReflectionUnionType extends ReflectionType
{
    /** @var non-empty-list<ReflectionNamedType|ReflectionIntersectionType> */
    private array $types;

    /** @internal */
    public function __construct(
        Reflector $reflector,
        ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty $owner,
        UnionType $type,
    ) {
        /** @var non-empty-list<ReflectionNamedType|ReflectionIntersectionType> $types */
        $types = array_map(static function (Identifier|Name|IntersectionType $type) use ($reflector, $owner): ReflectionNamedType|ReflectionIntersectionType {
            $type = ReflectionType::createFromNode($reflector, $owner, $type);
            assert($type instanceof ReflectionNamedType || $type instanceof ReflectionIntersectionType);

            return $type;
        }, $type->types);

        $this->types = $types;
    }

    /** @return non-empty-list<ReflectionNamedType|ReflectionIntersectionType> */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function allowsNull(): bool
    {
        foreach ($this->types as $type) {
            if ($type->allowsNull()) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return implode('|', array_map(static function (ReflectionType $type): string {
            if ($type instanceof ReflectionIntersectionType) {
                return sprintf('(%s)', $type->__toString());
            }

            return $type->__toString();
        }, $this->types));
    }
}
