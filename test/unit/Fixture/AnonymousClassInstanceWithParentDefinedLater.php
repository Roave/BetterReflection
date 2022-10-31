<?php

namespace Roave\BetterReflectionTest\Fixture;

$anonymous = new class extends FixtureParent
{
    public function test(): void
    {
    }
};

class FixtureParent
{
    public function test(): void
    {

    }
}
