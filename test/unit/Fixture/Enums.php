<?php

namespace Roave\BetterReflectionTest\Fixture;

enum PureEnum implements InterfaceForEnum
{
    case ONE;
    case TWO;
    case THREE;

    public function someMethod()
    {
    }
}

enum IntEnum: int implements InterfaceForEnum
{
    case ONE = 1;
    case TWO = 2;
    case THREE = 3;
    case FOUR = 4;

    public function someMethod()
    {
    }
}

enum StringEnum: string implements InterfaceForEnum
{
    case ONE
        = 'one';
    case TWO
        = 'two';
    case THREE
        = 'three';
    case FOUR
        = 'four';
    case FIVE
        = 'five';

    public function someMethod()
    {
    }
}

enum DocComment
{
    /** With doccomment */
    case WITH_DOCCOMMENT;

    case NO_DOCCOMMENT;
}

enum IsDeprecated
{
    /**
     * @deprecated
     */
    case IS_DEPRECATED;

    /**
     * @deprecatedIsNot
     */
    case IS_NOT_DEPRECATED;
}

interface InterfaceForEnum
{
}
