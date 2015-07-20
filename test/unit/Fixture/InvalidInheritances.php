<?php

namespace BetterReflectionTest\Fixture\InvalidInheritances
{
    interface AInterface {}
    class AClass {}
    trait ATrait {}

    interface InterfaceExtendingClass extends AClass {}
    class ClassExtendingInterface extends AInterface {}
    class ClassExtendingTrait extends ATrait {}
}
