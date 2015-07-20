<?php

namespace BetterReflectionTest\Fixture\InvalidInheritances
{
    interface AInterface {}
    class AClass {}

    interface InterfaceExtendingClass extends AClass {}
    class ClassExtendingInterface extends AInterface {}
}
