<?php

namespace Roave\BetterReflectionTest\Fixture;

/**
 * Class comment
 */
enum EnumPureForSourceStubber implements \Roave\BetterReflectionTest\Fixture\ImplementedInterfaceForEnumSourceStubber, \UnitEnum
{
    use \Roave\BetterReflectionTest\Fixture\UsedTraitToAliasForEnumSourceStubber {
        \Roave\BetterReflectionTest\Fixture\UsedTraitToAliasForEnumSourceStubber::methodFromTraitToAlias as aliasMethodFromTrait;
    }
    use \Roave\BetterReflectionTest\Fixture\UsedTraitForEnumSourceStubber;
    case ENUM_CASE;
    /**
     * Constant comment
     */
    public const CONSTANT = 1;
    /**
     * Method comment
     */
    public function methodFromInterface()
    {
    }
    public static function cases() : array
    {
    }
}
