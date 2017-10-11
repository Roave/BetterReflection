<?php

namespace Rector\BetterReflectionTest\Fixture;

class Php71NullableParameterTypeDeclarations
{
    public function foo(
        ?int $nullableIntParam,
        ?\stdClass $nullableClassParam,
        ?string $nullableStringParamWithDefaultValue = null
    ) {
    }
}
