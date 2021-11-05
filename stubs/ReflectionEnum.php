<?php

if (class_exists(ReflectionEnum::class, false)) {
    return;
}

class ReflectionEnum extends ReflectionClass
{
    public function __construct(object|string $objectOrClass) {}

    public function hasCase(string $name): bool
    {
    }

    public function getCases(): array
    {
    }

    public function getCase(string $name): ReflectionEnumUnitCase
    {
    }

    public function isBacked(): bool
    {
    }

    public function getBackingType(): ?ReflectionType
    {
    }
}
