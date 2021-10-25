<?php

if (interface_exists(UnitEnum::class, false)) {
    return;
}

interface UnitEnum
{
    /**
     * @return static[]
     */
    public static function cases(): array;
}

