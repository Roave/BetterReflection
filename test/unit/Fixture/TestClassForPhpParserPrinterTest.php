<?php

namespace Roave\BetterReflectionTest\Fixture;

use Roave\BetterReflection\TypesFinder\FindTypeFromAst;

class TestClassForPhpParserPrinterTest
{
    public function foo() : FindTypeFromAst
    {
        return new FindTypeFromAst();
    }
}
