<?php

namespace Roave\BetterReflectionTest\Fixture;

use Roave\BetterReflection\TypesFinder\FindReturnType;

class TestClassForPhpParserPrinterTest
{
    public function foo() : FindReturnType
    {
        return new FindReturnType();
    }
}
