<?php

namespace Roave\BetterReflectionTest\Fixture {

    use Roave\BetterReflectionTest\FixtureOther\OtherClass;

    class ParentClassWithConstant
    {
        public const PARENT_CONST = 'parent';
    }

    class ClassWithConstantsAsDefaultValues extends ParentClassWithConstant
    {
        public const MY_CONST = 'my';

        public function method($param1 = self::MY_CONST, $param2 = self::PARENT_CONST, $param3 = OtherClass::MY_CONST)
        {
        }
    }
}

namespace Roave\BetterReflectionTest\FixtureOther {
    class OtherClass
    {
        public const MY_CONST = 'other';
    }
}
