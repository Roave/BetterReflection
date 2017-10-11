<?php

namespace Rector\BetterReflectionTest\Fixture;

class Php7ParameterTypeDeclarations
{
    public function foo(
        int $intParam,
        \stdClass $classParam,
        $noTypeParam,
        string $stringParamAllowsNull = null
    ) {
    }
}
