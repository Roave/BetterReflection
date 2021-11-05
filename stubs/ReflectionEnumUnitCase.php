<?php

if (class_exists(ReflectionEnumUnitCase::class, false)) {
    return;
}

class ReflectionEnumUnitCase extends ReflectionClassConstant
{
    public function __construct(object|string $class, string $constant)
    {
    }

    public function getValue(): UnitEnum
    {
    }

    /**
     * @return ReflectionEnum
     */
    public function getEnum(): ReflectionEnum
    {
    }
}
