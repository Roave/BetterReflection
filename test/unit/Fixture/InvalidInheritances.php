<?php

namespace BetterReflectionTest\Fixture\InvalidInheritances
{
    interface AInterface {}
    class AClass {}
    trait ATrait {}

    interface InterfaceExtendingClass extends AClass {}
    interface InterfaceExtendingTrait extends ATrait {}
    class ClassExtendingInterface extends AInterface {}
    class ClassExtendingTrait extends ATrait {}
}
