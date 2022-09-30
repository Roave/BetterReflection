<?php

namespace Roave\BetterReflectionTest\Fixture\InvalidTraitUses;

trait TraitUsesSelf
{
    use TraitUsesSelf;
}

trait Trait1
{
    use Trait2;
}
trait Trait2
{
    use Trait1;
}
trait Trait3
{
    use Trait2;
}

class Class1
{
    use TraitUsesSelf;
}

class Class2
{
    use Trait1;
}
