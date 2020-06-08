<?php

namespace Roave\BetterReflectionTest\ClassesWithPublicOrNonPublicConstructor;


class ClassWithPublicConstructor
{
    public function __construct()
    {
    }
}

class ClassWithoutConstructor
{
}

class ClassWithPrivateConstructor
{
    private function __construct()
    {
    }
}

class ClassWithProtectedConstructor
{
    protected function __construct()
    {
    }
}

class ClassWithExtendedConstructor extends ClassWithPrivateConstructor
{
}
