<?php

namespace Roave\BetterReflectionTest\Fixture;

interface StringCastMethodsInterface
{
    public function prototypeMethod();
}

abstract class StringCastMethodsParent
{
    public function overwrittenMethod()
    {
    }
}

abstract class StringCastMethods extends StringCastMethodsParent implements StringCastMethodsInterface
{
    public function __construct()
    {
    }

    public function __destruct()
    {
    }

    public function publicMethod()
    {
    }

    protected function protectedMethod()
    {
    }

    private function privateMethod()
    {
    }

    final public function finalPublicMethod()
    {
    }

    abstract public function abstractPublicMethod();

    public static function staticPublicMethod()
    {
    }

    function noVisibility()
    {
    }

    public function overwrittenMethod()
    {
    }

    public function prototypeMethod()
    {
    }

    public function methodWithParameters($a, $b)
    {
    }

    public function methodWithReturnType(): string
    {
    }
}
