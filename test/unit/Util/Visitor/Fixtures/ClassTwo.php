<?php

namespace BetterReflectionTest\Util\Visitor\Fixtures;

class ClassTwo
{
    /**
     * @var ClassThree
     */
    public $classThree;

    public function getClassThree(int $number, ClassTwo $object): ClassThree
    {
    }
}
