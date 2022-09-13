<?php

namespace Roave\BetterReflectionTest\Fixture;

trait TraitWithConstants
{
    const CLASS_WINS = 0;
    const PARENT_WINS = 0;
    const TRAIT_WINS = 0;
}

class ParentClassWithConstants
{
    const PARENT_WINS = 0;
}

class ClassWithConstants extends ParentClassWithConstants
{
    use TraitWithConstants;

    const CLASS_WINS = 0;
}

interface InterfaceWithConstants
{
    const CLASS_WINS = 0;
    const INTERFACE_WINS = 0;
}

class OtherClassWithConstants implements InterfaceWithConstants
{
    use TraitWithConstants;

    const CLASS_WINS = 0;
}
