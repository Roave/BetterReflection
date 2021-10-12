<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\UnionType;

use function array_map;
use function assert;
use function implode;

class ReflectionUnionType extends ReflectionType
{
    /** @var list<ReflectionNamedType> */
    private array $types;

    public function __construct(UnionType $type, bool $allowsNull)
    {
        parent::__construct($allowsNull);
        $this->types = array_map(static function ($type): ReflectionNamedType {
            $reflectionType = ReflectionType::createFromTypeAndReflector($type);
            assert($reflectionType instanceof ReflectionNamedType);

            return $reflectionType;
        }, $type->types);
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
        return implode('|', array_map(static fn (ReflectionType $type): string => (string) $type, $this->types));
    }
}
