<?php

namespace Roave\BetterReflectionTest\Fixture;

use ArrayIterator;
use stdClass;

class PHP81ClassForSourceStubber
{
    public final const FINAL_CONST = 'finalConst';

    public readonly int $readOnly;

    public function getIntersectionType(): ArrayIterator&stdClass
    {
    }
}
