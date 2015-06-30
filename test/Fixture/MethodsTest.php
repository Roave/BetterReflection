<?php

namespace BetterReflectionTest\Fixture;

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

    /**
     * @param string $parameter1
     * @param int|float $parameter2
     */
    public function methodWithParameters($parameter1, $parameter2)
    {
    }

    public function methodWithOptionalParameters($parameter, $optionalParameter = null)
    {
    }
}
