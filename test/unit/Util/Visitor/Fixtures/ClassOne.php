<?php

namespace BetterReflectionTest\Util\Visitor\Fixtures;

class ClassOne
{
    /**
     * @var ClassTwo
     */
    public $classTwo;

    public function getClassThree(int $number, ClassTwo $object): ClassThree
    {
    }

    public function getClassTwo(): ClassTwo
    {
    }
}
