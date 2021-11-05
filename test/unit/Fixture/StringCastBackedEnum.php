<?php

namespace Roave\BetterReflectionTest\Fixture;

enum StringCastBackedEnum: string
{
    case ENUM_CASE = 'string';

    const CONSTANT = 'constant';
}
