<?php

namespace Roave\BetterReflectionTest\Fixture;

\define('SOME_DEFINED_VALUE', 1);

abstract class Methods
{
    const SOME_CONST = 1;

    public function __construct(private $promotedParameter, $notPromotedParameter)
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

    public function methodIsArrayParameters(
        $noTypeParameter,
        bool $boolParameter,
        array $arrayParameter,
        ArRaY $arrayCaseInsensitiveParameter,
        ?array $nullableArrayParameter,
        null|array $unionArrayParameterNullFirst,
        array|null $unionArrayParameterNullLast,
        ARRAY|NULL $unionArrayParameterNullUppercase,
        string|bool $unionNotArrayParameter,
        array|string|null $unionWithArrayNotArrayParameter,
        array|object $unionWithArrayAndObjectNotArrayParameter,
        \stdClass&\Iterator $intersectionNotArrayParameter,
    )
    {
    }

    public function methodIsCallableParameters(
        $noTypeParameter,
        bool $boolParameter,
        callable $callableParameter,
        cAlLaBlE $callableCaseInsensitiveParameter,
        ?callable $nullableCallableParameter,
        null|callable $unionCallableParameterNullFirst,
        callable|null $unionCallableParameterNullLast,
        CALLABLE|NULL $unionCallableParameterNullUppercase,
        string|bool $unionNotCallableParameter,
        callable|string|null $unionWithCallableNotCallableParameter,
        callable|object $unionWithCallableAndObjectNotArrayParameter,
        \stdClass&\Iterator $intersectionNotCallableParameter,
    )
    {
    }

    public function methodGetClassParameters(
        $untyped,
        array $array,
        \stdClass $object,
        string|\stdClass $unionWithClass,
        string|bool $unionWithoutClass,
        \stdClass&\Iterator $intersection,
    )
    {
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

    public function methodWithConstAsDefault($intDefault = 1, $constDefault = self::SOME_CONST, $definedDefault = SOME_DEFINED_VALUE)
    {
    }

    public function methodWithUpperCasedDefaults($boolUpper = TRUE, $boolLower = false, $nullUpper = NULL)
    {
    }
}
