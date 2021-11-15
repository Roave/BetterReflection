<?php

namespace Roave\BetterReflectionTest\Fixture;

class PHP81ClassForSourceStubber
{
    public final const FINAL_CONST = 'finalConst';
    public readonly int $readOnly;
    public function getIntersectionType() : \ArrayIterator&\stdClass
    {
    }
}
