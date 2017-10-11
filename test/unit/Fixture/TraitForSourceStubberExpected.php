<?php
namespace Rector\BetterReflectionTest\Fixture;

trait TraitForSourceStubber
{
    use \Rector\BetterReflectionTest\Fixture\OtherTraitForSourceStubber;
    public function methodFromTrait()
    {
    }
}
