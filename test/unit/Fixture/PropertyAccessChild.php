<?php

namespace Roave\BetterReflectionTest\Fixture;

class PropertyAccessChild extends PropertyAccessBase
{
    public int $propertyOverriddenByChild = 2;
    protected int $childProtectedProperty = 1;
}
