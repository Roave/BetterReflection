<?php

namespace Roave\BetterReflectionTest\Fixture;

class PhpParameterTypeDeclarations
{
    public function foo(
        int $intParam,
        \stdClass $classParam,
        $noTypeParam,
    ) {
    }
}
