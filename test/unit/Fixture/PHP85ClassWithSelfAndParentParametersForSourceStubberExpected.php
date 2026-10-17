<?php

namespace Roave\BetterReflectionTest\Fixture;

abstract class ClassWithSelfAndParentParametersForSourceStubber extends \Roave\BetterReflectionTest\Fixture\ParentClassWithSelfAndParentParametersForSourceStubber
{
    public function methodWithSelfAndParentParameters(\Roave\BetterReflectionTest\Fixture\ClassWithSelfAndParentParametersForSourceStubber $self, \Roave\BetterReflectionTest\Fixture\ParentClassWithSelfAndParentParametersForSourceStubber $parent): void
    {
    }
}
