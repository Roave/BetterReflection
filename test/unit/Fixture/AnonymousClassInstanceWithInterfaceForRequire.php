<?php

namespace Roave\BetterReflectionTest\Fixture;

interface FixtureInterfaceRequire {
    public function test(): void;
}

interface FixtureSecondInterfaceRequire {
    public function secondTest(): void;
}

return new class implements FixtureInterfaceRequire, FixtureSecondInterfaceRequire
{
    public function test(): void
    {
    }

    public function secondTest(): void
    {
    }
};
