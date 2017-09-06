<?php

namespace Roave\BetterReflectionTest\Fixture;

class StringCastParametersParent
{
}

abstract class StringCastParameters extends StringCastParametersParent
{
    public function parametersWithBuiltInTypes(
        string $string,
        int $int,
        float $float,
        bool $bool,
        callable $callable,
        self $self,
        parent $parent,
        array $array,
        iterable $iterable,
        object $object
    )
    {
    }

    public function parametersWithNullableBuiltInTypes(
        ?string $string,
        ?int $int,
        ?float $float,
        ?bool $bool,
        ?callable $callable,
        ?self $self,
        ?parent $parent,
        ?array $array,
        ?iterable $iterable,
        ?object $object
    )
    {
    }

    public function parametersWithNullableBuiltInTypesWithDefaultValue(
        ?string $string = 'stringstringstringstringstringstring',
        ?int $int = 0,
        ?float $float = 0.0,
        ?bool $bool = true,
        ?callable $callable = null,
        ?self $self = null,
        ?parent $parent = null,
        ?array $array = [],
        ?iterable $iterable = [],
        ?object $object = null
    )
    {
    }

    public function parametersWithDefaultValue(
        $string = 'string',
        $int = 0,
        $float = 0.0,
        $bool = true,
        $callable = null,
        $self = null,
        $parent = null,
        $array = [],
        $iterable = [],
        $object = null
    )
    {
    }

    public function variadicParameter(...$variadic)
    {
    }

    public function passedByReferenceParameter(&$passedByReference)
    {
    }
}
