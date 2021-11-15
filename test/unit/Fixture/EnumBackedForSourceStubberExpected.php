<?php

namespace Roave\BetterReflectionTest\Fixture;

/**
 * Class comment
 */
enum EnumBackedForSourceStubber : int implements \BackedEnum
{
    case ONE = 1;
    case TWO = 2;
    public static function cases() : array
    {
    }
    public static function from(string|int $value) : static
    {
    }
    public static function tryFrom(string|int $value) : ?static
    {
    }
}
