<?php
namespace Roave\BetterReflectionTest\Fixture;

trait TraitForSourceStubber
{
    use \Roave\BetterReflectionTest\Fixture\OtherTraitForSourceStubber;
    public function methodFromTrait()
    {
    }
}
