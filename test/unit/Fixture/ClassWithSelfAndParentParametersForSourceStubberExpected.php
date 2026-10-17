<?php

namespace Roave\BetterReflectionTest\Fixture;

abstract class ClassWithSelfAndParentParametersForSourceStubber extends \Roave\BetterReflectionTest\Fixture\ParentClassWithSelfAndParentParametersForSourceStubber
{
    public function methodWithSelfAndParentParameters(self $self, parent $parent): void
    {
    }
}
