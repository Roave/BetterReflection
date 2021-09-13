<?php

namespace Roave\BetterReflectionTest\Fixture;

class PhpParameterTypeDeclarations
{
    public const NULLABLE = null;

    public function foo(
        int $intParam,
        \stdClass $classParam,
        $noTypeParam,
        string $stringParamAllowsNull = null,
        string $stringWithNullConstantDefaultValueDoesNotAllowNull = self::NULLABLE
    ) {
    }
}
