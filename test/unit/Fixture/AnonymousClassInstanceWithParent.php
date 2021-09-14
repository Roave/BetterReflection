<?php

namespace Roave\BetterReflectionTest\Fixture;

class FixtureParent {
    public function test(): void
    {

    }
}

interface FixtureSecondInterface {
    public function secondTest(): void;
}

return new class extends FixtureParent implements FixtureSecondInterface
{
    public function secondTest(): void
    {
    }
};
