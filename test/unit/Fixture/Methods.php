<?php

namespace Roave\BetterReflectionTest\Fixture;

\define('SOME_DEFINED_VALUE', 1);

abstract class Methods
{
    const SOME_CONST = 1;

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
        \Roave\BetterReflectionTest\Fixture\ClassForHinting $fullyQualifiedClassParameter,
        array $arrayParameter,
        callable $callableParameter
    ) {
    }

    public function methodWithVariadic($nonVariadicParameter, ...$variadicParameter)
    {
    }

    public function methodWithFirstParameterWithDefaultValueAndSecondParameterIsVariadic($parameterWithDefaultValue = null, ...$variadicParameter)
    {
    }

    public function methodWithReference($nonRefParameter, &$refParameter)
    {
    }

    public function methodWithNonOptionalDefaultValue($firstParameter = 'someValue', $secondParameter)
    {
    }

    public function methodToCheckAllowsNull($allowsNull, \stdClass $hintDisallowNull, \stdClass $hintAllowNull = null)
    {
    }

    public function methodWithConstAsDefault($intDefault = 1, $constDefault = self::SOME_CONST, $definedDefault = SOME_DEFINED_VALUE)
    {
    }

    public function methodWithUpperCasedDefaults($boolUpper = TRUE, $boolLower = false, $nullUpper = NULL)
    {
    }
}
