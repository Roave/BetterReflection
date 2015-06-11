<?php

namespace AsgrimTest\Fixture;

abstract class MethodsTest
{
    public function __construct()
    {
    }

    public function publicMethod()
    {
    }

    private function privateMethod()
    {
    }

    protected function protectedMethod()
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

    public function __destruct()
    {
    }

    public function methodWithParameters($parameter1, $parameter2)
    {
    }
}
