<?php

namespace Roave\BetterReflectionTest\Fixture;

trait TraitStaticPropertyGetSetToUse
{
    public static $staticProperty;
}

class ClassUsesTraitStaticPropertyGetSet
{
    use TraitStaticPropertyGetSetToUse;
}
