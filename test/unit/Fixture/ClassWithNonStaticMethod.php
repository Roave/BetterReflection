<?php

namespace Roave\BetterReflectionTest\Fixture;

class ClassWithNonStaticMethod
{
    private $constant = 100;

    public function sum(int $a, int $b) : int
    {
        return $this->constant + $a + $b;
    }
}
