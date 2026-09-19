<?php

namespace Roave\BetterReflectionTest\Fixture;

class PropertyAccessBase
{
    public int $publicProperty = 1;
    protected int $protectedProperty = 1;
    private int $privateProperty = 1;

    public protected(set) int $protectedSetterProperty = 1;
    public private(set) int $privateSetterProperty = 1;

    protected int $propertyOverriddenByChild = 1;

    public static int $staticInitialized = 1;
    public static int $staticUninitialized;
    public static $staticWithoutType;

    public readonly int $readonlyProperty;
}
