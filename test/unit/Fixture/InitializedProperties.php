<?php

namespace Roave\BetterReflectionTest\Fixture;

class InitializedProperties
{
    public $withoutType;
    public static $staticWithoutType;

    public int $withType;
    public static int $staticWithType;
    public static int $staticWithTypeAndDefault = 0;

    public int $withTypeInitialized;

    public function __construct()
    {
        $this->withTypeInitialized = 0;
    }
}
