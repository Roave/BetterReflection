<?php

if (class_exists(ReflectionEnumBackedCase::class, false)) {
    return;
}

class ReflectionEnumBackedCase extends ReflectionEnumUnitCase
{
    public function getBackingValue(): int|string
    {
    }
}
