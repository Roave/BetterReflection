<?php

namespace Roave\BetterReflectionTest\Fixture;

interface FixtureInterface {
    public function test(): void;
}

interface FixtureSecondInterface {
    public function secondTest(): void;
}

return new class implements FixtureInterface, FixtureSecondInterface
{
    public function test(): void
    {
    }

    public function secondTest(): void
    {
    }
};
