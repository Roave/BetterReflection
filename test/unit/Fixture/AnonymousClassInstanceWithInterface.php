<?php

interface FixtureInterface {
    public function test(): void;
}

return new class implements FixtureInterface
{
    public function test(): void
    {
    }
};