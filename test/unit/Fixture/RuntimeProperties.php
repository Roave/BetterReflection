<?php

namespace Roave\BetterReflectionTest\Fixture;

use AllowDynamicProperties;

#[AllowDynamicProperties]
class RuntimeProperties
{
    public $publicProperty;
    protected $protectedProperty;
    private $privateProperty;
    static $staticProperty;
}
