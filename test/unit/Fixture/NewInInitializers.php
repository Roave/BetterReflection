<?php

namespace Roave\BetterReflectionTest\Fixture;

use const PHP_VERSION_ID;

const SOME_CONSTANT = 'constant';

class ClassWithNewInInitializers
{
    public function methodWithInitializer($parameterWithInitializer = new \ArrayObject(['a', 'b', SOME_CONSTANT, PHP_VERSION_ID, self::class, new \stdClass()]))
    {
    }
}
