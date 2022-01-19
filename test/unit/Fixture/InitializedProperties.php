<?php

namespace Roave\BetterReflectionTest\Fixture;

use Error;

class InitializedProperties
{
    public $withoutType;
    public static $staticWithoutType;

    public int $withType;
    public static int $staticWithType;
    public static int $staticWithTypeAndDefault = 0;

    public int $withTypeInitialized;

    public int $toBeRemoved;

    public function __construct()
    {
        $this->withTypeInitialized = 0;
    }

    public function __get($property)
    {
        throw new Error();
    }
}
