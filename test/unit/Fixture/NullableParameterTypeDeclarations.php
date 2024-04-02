<?php

namespace Roave\BetterReflectionTest\Fixture;

class NullableParameterTypeDeclarations
{
    public const NULLABLE = null;

    public function foo(
        \stdClass $classParam,
        $noTypeParam,
        ?string $nullableStringAllowsNull,
        null|string $unionWithNullOnFirstPositionAllowsNull,
        string|null $unionWithNullOnLastPositionAllowsNull,
        string $stringParamWithNullDefaultValueAllowsNull = null,
        string $stringWithNullConstantDefaultValueDoesNotAllowNull = self::NULLABLE
    ) {
    }

    public function __construct(
        string $stringParamWithNullDefaultValueAllowsNull = null,
        public string $stringPromotedPropertyWithNullDefaultValueDoesNotAllowNull = null
    )
    {

    }
}
