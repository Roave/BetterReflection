<?php

namespace Rector\BetterReflectionTest\Fixture {

    use Rector\BetterReflectionTest\FixtureOther\OtherClass;
    use Rector\BetterReflectionTest\FixtureOther\OTHER_NAMESPACE_CONST;

    const THIS_NAMESPACE_CONST = 'this_namespace';

    class ParentClassWithConstant
    {
        public const PARENT_CONST = 'parent';
    }

    class ClassWithConstantsAsDefaultValues extends ParentClassWithConstant
    {
        public const MY_CONST = 'my';

        public function method($param1 = self::MY_CONST, $param2 = self::PARENT_CONST,
            $param3 = OtherClass::MY_CONST, $param4 = THIS_NAMESPACE_CONST,
            $param5 = OTHER_NAMESPACE_CONST)
        {
        }
    }
}

namespace Rector\BetterReflectionTest\FixtureOther {

    const OTHER_NAMESPACE_CONST = 'other_namespace';

    class OtherClass
    {
        public const MY_CONST = 'other';
    }
}
