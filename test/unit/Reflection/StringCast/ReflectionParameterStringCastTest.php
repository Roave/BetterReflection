<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\StringCastParameters;

/**
 * @covers \Roave\BetterReflection\Reflection\StringCast\ReflectionParameterStringCast
 */
class ReflectionParameterStringCastTest extends TestCase
{
    private Locator $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function toStringProvider(): array
    {
        return [
            ['parametersWithBuiltInTypes', 'string', 'Parameter #0 [ <required> string $string ]'],
            ['parametersWithBuiltInTypes', 'int', 'Parameter #1 [ <required> integer $int ]'],
            ['parametersWithBuiltInTypes', 'float', 'Parameter #2 [ <required> float $float ]'],
            ['parametersWithBuiltInTypes', 'bool', 'Parameter #3 [ <required> boolean $bool ]'],
            ['parametersWithBuiltInTypes', 'callable', 'Parameter #4 [ <required> callable $callable ]'],
            ['parametersWithBuiltInTypes', 'self', 'Parameter #5 [ <required> self $self ]'],
            ['parametersWithBuiltInTypes', 'parent', 'Parameter #6 [ <required> parent $parent ]'],
            ['parametersWithBuiltInTypes', 'array', 'Parameter #7 [ <required> array $array ]'],
            ['parametersWithBuiltInTypes', 'iterable', 'Parameter #8 [ <required> iterable $iterable ]'],
            ['parametersWithBuiltInTypes', 'object', 'Parameter #9 [ <required> object $object ]'],

            ['parametersWithNullableBuiltInTypes', 'string', 'Parameter #0 [ <required> string or NULL $string ]'],
            ['parametersWithNullableBuiltInTypes', 'int', 'Parameter #1 [ <required> integer or NULL $int ]'],
            ['parametersWithNullableBuiltInTypes', 'float', 'Parameter #2 [ <required> float or NULL $float ]'],
            ['parametersWithNullableBuiltInTypes', 'bool', 'Parameter #3 [ <required> boolean or NULL $bool ]'],
            ['parametersWithNullableBuiltInTypes', 'callable', 'Parameter #4 [ <required> callable or NULL $callable ]'],
            ['parametersWithNullableBuiltInTypes', 'self', 'Parameter #5 [ <required> self or NULL $self ]'],
            ['parametersWithNullableBuiltInTypes', 'parent', 'Parameter #6 [ <required> parent or NULL $parent ]'],
            ['parametersWithNullableBuiltInTypes', 'array', 'Parameter #7 [ <required> array or NULL $array ]'],
            ['parametersWithNullableBuiltInTypes', 'iterable', 'Parameter #8 [ <required> iterable or NULL $iterable ]'],
            ['parametersWithNullableBuiltInTypes', 'object', 'Parameter #9 [ <required> object or NULL $object ]'],

            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'string', 'Parameter #0 [ <optional> string or NULL $string = \'stringstringstr...\' ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'int', 'Parameter #1 [ <optional> integer or NULL $int = 0 ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'float', 'Parameter #2 [ <optional> float or NULL $float = 0.0 ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'bool', 'Parameter #3 [ <optional> boolean or NULL $bool = true ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'callable', 'Parameter #4 [ <optional> callable or NULL $callable = NULL ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'self', 'Parameter #5 [ <optional> self or NULL $self = NULL ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'parent', 'Parameter #6 [ <optional> parent or NULL $parent = NULL ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'array', 'Parameter #7 [ <optional> array or NULL $array = Array ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'iterable', 'Parameter #8 [ <optional> iterable or NULL $iterable = Array ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'object', 'Parameter #9 [ <optional> object or NULL $object = NULL ]'],

            ['parametersWithDefaultValue', 'string', 'Parameter #0 [ <optional> $string = \'string\' ]'],
            ['parametersWithDefaultValue', 'int', 'Parameter #1 [ <optional> $int = 0 ]'],
            ['parametersWithDefaultValue', 'float', 'Parameter #2 [ <optional> $float = 0.0 ]'],
            ['parametersWithDefaultValue', 'bool', 'Parameter #3 [ <optional> $bool = true ]'],
            ['parametersWithDefaultValue', 'callable', 'Parameter #4 [ <optional> $callable = NULL ]'],
            ['parametersWithDefaultValue', 'self', 'Parameter #5 [ <optional> $self = NULL ]'],
            ['parametersWithDefaultValue', 'parent', 'Parameter #6 [ <optional> $parent = NULL ]'],
            ['parametersWithDefaultValue', 'array', 'Parameter #7 [ <optional> $array = Array ]'],
            ['parametersWithDefaultValue', 'iterable', 'Parameter #8 [ <optional> $iterable = Array ]'],
            ['parametersWithDefaultValue', 'object', 'Parameter #9 [ <optional> $object = NULL ]'],

            ['variadicParameter', 'variadic', 'Parameter #0 [ <optional> ...$variadic ]'],
            ['passedByReferenceParameter', 'passedByReference', 'Parameter #0 [ <required> &$passedByReference ]'],
        ];
    }

    /**
     * @dataProvider toStringProvider
     */
    public function testToString(string $methodName, string $parameterName, string $expectedString): void
    {
        $reflector        = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastParameters.php', $this->astLocator));
        $classReflection  = $reflector->reflect(StringCastParameters::class);
        $methodReflection = $classReflection->getMethod($methodName);

        self::assertSame($expectedString, (string) $methodReflection->getParameter($parameterName));
    }
}
