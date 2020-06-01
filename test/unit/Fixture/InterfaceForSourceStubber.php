<?php

namespace Roave\BetterReflectionTest\Fixture;

use JsonSerializable;
use Serializable;

/**
 * Interface comment
 */
interface InterfaceForSourceStubber extends JsonSerializable, Serializable
{
    public function someMethod();
}
