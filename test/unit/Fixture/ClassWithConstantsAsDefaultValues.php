<?php

namespace Roave\BetterReflectionTest\Fixture {

    use Roave\BetterReflectionTest\FixtureOther\OtherClass;
    use const Roave\BetterReflectionTest\FixtureOther\OTHER_NAMESPACE_CONST;

    const THIS_NAMESPACE_CONST = 'this_namespace';
    const UNSURE_CONST = 'this';

    class ParentClassWithConstant
    {
        public const PARENT_CONST = 'parent';
    }

    trait TraitWithConstantsAsDefaultValues
    {
        public function methodFromTrait($param1 = self::MY_CONST)
        {
        }
    }

    class ClassWithConstantsAsDefaultValues extends ParentClassWithConstant
    {
        use TraitWithConstantsAsDefaultValues;

        public const MY_CONST = 'my';

        public function method($param1 = self::MY_CONST, $param2 = self::PARENT_CONST,
            $param3 = OtherClass::MY_CONST, $param4 = THIS_NAMESPACE_CONST,
            $param5 = OTHER_NAMESPACE_CONST, $param6 = self::class,
            $param7 = GLOBAL_CONST, $param8 = UNSURE_CONST,
        )
        {
        }
    }
}

namespace Roave\BetterReflectionTest\FixtureOther {

    const OTHER_NAMESPACE_CONST = 'other_namespace';

    class OtherClass
    {
        public const MY_CONST = 'other';
    }
}

namespace {

    const GLOBAL_CONST = 1;
    const UNSURE_CONST = 'this_not';

}
