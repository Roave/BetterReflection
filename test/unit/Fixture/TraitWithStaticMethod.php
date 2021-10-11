<?php

namespace Roave\BetterReflectionTest\Fixture;

trait TraitWithStaticMethod
{
    public static function sum(int $a, int $b) : int
    {
        return $a + $b;
    }
}
