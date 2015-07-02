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

    public function methodWithExplicitTypedParameters(
        \stdClass $stdClassParameter,
        ClassForHinting $namespaceClassParameter,
        \BetterReflectionTest\Fixture\ClassForHinting $fullyQualifiedClassParameter,
        array $arrayParameter,
        callable $callableParameter
    ) {
    }

    public function methodWithVariadic($nonVariadicParameter, ...$variadicParameter)
    {
    }

    public function methodWithReference($nonRefParameter, &$refParameter)
    {
    }

    public function methodWithNonOptionalDefaultValue($firstParameter = 'someValue', $secondParameter)
    {
    }
}
