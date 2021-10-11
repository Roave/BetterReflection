<?php

namespace Roave\BetterReflectionTest\Fixture;

trait TraitWithStaticMethodToUse
{
    public static function getClass() : string
    {
        return static::class;
    }
}

class ClassUsesTraitWithStaticMethod
{
    use TraitWithStaticMethodToUse;
}
