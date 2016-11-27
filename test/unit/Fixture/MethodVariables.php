<?php

namespace BetterReflectionTest\Fixture;

define('SOME_DEFINED_VALUE', 1);

class MethodVariables
{
    public function methodOne(int $arg1)
    {
        $foobar = $arg1;
    }
}
