<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflector\DefaultReflector;
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
            ['parametersWithBuiltInTypes', 'int', 'Parameter #1 [ <required> int $int ]'],
            ['parametersWithBuiltInTypes', 'float', 'Parameter #2 [ <required> float $float ]'],
            ['parametersWithBuiltInTypes', 'bool', 'Parameter #3 [ <required> bool $bool ]'],
            ['parametersWithBuiltInTypes', 'callable', 'Parameter #4 [ <required> callable $callable ]'],
            ['parametersWithBuiltInTypes', 'self', 'Parameter #5 [ <required> self $self ]'],
            ['parametersWithBuiltInTypes', 'parent', 'Parameter #6 [ <required> parent $parent ]'],
            ['parametersWithBuiltInTypes', 'array', 'Parameter #7 [ <required> array $array ]'],
            ['parametersWithBuiltInTypes', 'iterable', 'Parameter #8 [ <required> iterable $iterable ]'],
            ['parametersWithBuiltInTypes', 'object', 'Parameter #9 [ <required> object $object ]'],

            ['parametersWithNullableBuiltInTypes', 'string', 'Parameter #0 [ <required> ?string $string ]'],
            ['parametersWithNullableBuiltInTypes', 'int', 'Parameter #1 [ <required> ?int $int ]'],
            ['parametersWithNullableBuiltInTypes', 'float', 'Parameter #2 [ <required> ?float $float ]'],
            ['parametersWithNullableBuiltInTypes', 'bool', 'Parameter #3 [ <required> ?bool $bool ]'],
            ['parametersWithNullableBuiltInTypes', 'callable', 'Parameter #4 [ <required> ?callable $callable ]'],
            ['parametersWithNullableBuiltInTypes', 'self', 'Parameter #5 [ <required> ?self $self ]'],
            ['parametersWithNullableBuiltInTypes', 'parent', 'Parameter #6 [ <required> ?parent $parent ]'],
            ['parametersWithNullableBuiltInTypes', 'array', 'Parameter #7 [ <required> ?array $array ]'],
            ['parametersWithNullableBuiltInTypes', 'iterable', 'Parameter #8 [ <required> ?iterable $iterable ]'],
            ['parametersWithNullableBuiltInTypes', 'object', 'Parameter #9 [ <required> ?object $object ]'],

            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'string', 'Parameter #0 [ <optional> ?string $string = \'stringstringstr...\' ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'int', 'Parameter #1 [ <optional> ?int $int = 0 ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'float', 'Parameter #2 [ <optional> ?float $float = 0.0 ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'bool', 'Parameter #3 [ <optional> ?bool $bool = true ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'callable', 'Parameter #4 [ <optional> ?callable $callable = NULL ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'self', 'Parameter #5 [ <optional> ?self $self = NULL ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'parent', 'Parameter #6 [ <optional> ?parent $parent = NULL ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'array', 'Parameter #7 [ <optional> ?array $array = Array ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'iterable', 'Parameter #8 [ <optional> ?iterable $iterable = Array ]'],
            ['parametersWithNullableBuiltInTypesWithDefaultValue', 'object', 'Parameter #9 [ <optional> ?object $object = NULL ]'],

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
        $reflector        = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastParameters.php', $this->astLocator));
        $classReflection  = $reflector->reflectClass(StringCastParameters::class);
        $methodReflection = $classReflection->getMethod($methodName);

        self::assertSame($expectedString, (string) $methodReflection->getParameter($parameterName));
    }
}
