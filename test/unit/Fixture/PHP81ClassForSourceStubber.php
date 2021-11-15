<?php

namespace Roave\BetterReflectionTest\Fixture;

use ArrayIterator;
use stdClass;

class PHP81ClassForSourceStubber
{
    public final const FINAL_CONST = 'finalConst';

    public function getIntersectionType(): ArrayIterator&stdClass
    {
    }
}
