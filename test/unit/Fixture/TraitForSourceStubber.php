<?php

namespace Roave\BetterReflectionTest\Fixture;

trait OtherTraitForSourceStubber
{
}

trait TraitForSourceStubber
{
    use OtherTraitForSourceStubber;

    public function methodFromTrait()
    {
    }
}
