<?php

namespace Rector\BetterReflectionTest\Fixture;

class ClassWithStaticMethod
{
    public static function sum(int $a, int $b) : int
    {
        return $a + $b;
    }
}
