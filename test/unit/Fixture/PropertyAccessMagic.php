<?php

namespace Roave\BetterReflectionTest\Fixture;

class PropertyAccessMagic
{
    private int $secret = 1;
    private int $notIsset = 1;

    public function __get(string $name): mixed
    {
        return null;
    }

    public function __isset(string $name): bool
    {
        return $name === 'secret';
    }

    public function __set(string $name, mixed $value): void
    {
    }
}
