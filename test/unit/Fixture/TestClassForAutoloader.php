<?php

namespace Rector\BetterReflectionTest\Fixture;

class TestClassForAutoloader
{
    public function getValue()
    {
        return 'this is not the expected value';
    }
}
