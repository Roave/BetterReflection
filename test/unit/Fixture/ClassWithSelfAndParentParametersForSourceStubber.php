<?php

namespace Roave\BetterReflectionTest\Fixture;

class ParentClassWithSelfAndParentParametersForSourceStubber
{
}

abstract class ClassWithSelfAndParentParametersForSourceStubber extends ParentClassWithSelfAndParentParametersForSourceStubber
{
    public function methodWithSelfAndParentParameters(self $self, parent $parent) : void
    {
    }
}
