<?php

namespace Rector\BetterReflectionTest\Fixture;

use Rector\BetterReflection\TypesFinder\FindReturnType;

class TestClassForPhpParserPrinterTest
{
    public function foo() : FindReturnType
    {
        return new FindReturnType();
    }
}
