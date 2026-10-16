<?php

namespace Roave\BetterReflectionTest\Fixture;

class PropertyAccessMagicGetOnly
{
    private int $secret = 1;

    public function __get(string $name): mixed
    {
        return null;
    }
}
