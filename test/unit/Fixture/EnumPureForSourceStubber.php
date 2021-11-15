<?php

namespace Roave\BetterReflectionTest\Fixture;

interface ImplementedInterfaceForEnumSourceStubber
{
    public function methodFromInterface();
}

trait UsedTraitForEnumSourceStubber
{
    public function methodFromTrait()
    {
    }
}

trait UsedTraitToAliasForEnumSourceStubber
{
    public function methodFromTraitToAlias()
    {
    }
}

/**
 * Class comment
 */
enum EnumPureForSourceStubber implements ImplementedInterfaceForEnumSourceStubber
{
    use UsedTraitForEnumSourceStubber;
    use UsedTraitToAliasForEnumSourceStubber {
        UsedTraitToAliasForEnumSourceStubber::methodFromTraitToAlias as aliasMethodFromTrait;
    }

    case ENUM_CASE;

    /**
     * Method comment
     */
    public function methodFromInterface()
    {
    }

    /**
     * Constant comment
     */
    public const CONSTANT = 1;
}
