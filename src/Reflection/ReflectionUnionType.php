<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node;
use PhpParser\Node\UnionType;
use Roave\BetterReflection\Reflector\Reflector;

use function array_map;
use function array_values;
use function assert;
use function implode;

class ReflectionUnionType extends ReflectionType
{
    /** @var list<ReflectionNamedType> */
    private array $types;

    public function __construct(
        Reflector $reflector,
        ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty $owner,
        UnionType $type,
    ) {
        parent::__construct($reflector, $owner);

        $this->types = array_values(array_map(static function (Node\Identifier|Node\Name $type) use ($reflector, $owner): ReflectionNamedType {
            $type = ReflectionType::createFromNode($reflector, $owner, $type);
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
        foreach ($this->types as $type) {
            if ($type->getName() === 'null') {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return implode('|', array_map(static fn (ReflectionType $type): string => $type->__toString(), $this->types));
    }
}
