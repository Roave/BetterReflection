<?php

namespace BetterReflectionTest\Util\Visitor\Fixtures;

class ClassThree
{
    /**
     * @var ClassOne
     */
    public $classOne;

    public function getClassThree(int $number, ClassThree $object): ClassThree
    {
    }

    public function getClassOne(): ClassOne
    {
    }
}
