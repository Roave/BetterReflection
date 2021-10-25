<?php

if (interface_exists(BackedEnum::class, false)) {
    return;
}

interface BackedEnum extends UnitEnum
{
    public static function from(int|string $value): static;

    public static function tryFrom(int|string $value): ?static;
}
