<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\UnionType;

use function array_map;
use function implode;

class ReflectionUnionType extends ReflectionType
{
    /** @var ReflectionType[] */
    private array $types;

    public function __construct(UnionType $type, bool $allowsNull)
    {
        parent::__construct($allowsNull);
        $this->types = array_map(static fn ($type): ReflectionType => ReflectionType::createFromTypeAndReflector($type), $type->types);
    }

    /**
     * @return ReflectionType[]
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
