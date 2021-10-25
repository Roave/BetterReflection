<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Roave\BetterReflection\Reflection\StringCast\ReflectionFunctionStringCast
 */
class ReflectionFunctionStringCastTest extends TestCase
{
    private Locator $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->astLocator = $betterReflection->astLocator();
    }

    public function toStringProvider(): array
    {
        return [
            ['Roave\BetterReflectionTest\Fixture\functionWithoutParameters', "Function [ <user> function Roave\BetterReflectionTest\Fixture\\functionWithoutParameters ] {\n  @@ %s/Fixture/StringCastFunctions.php 5 - 7\n}"],
            ['Roave\BetterReflectionTest\Fixture\functionWithParameters', "Function [ <user> function Roave\BetterReflectionTest\Fixture\\functionWithParameters ] {\n  @@ %s/Fixture/StringCastFunctions.php 9 - 11\n\n  - Parameters [2] {\n    Parameter #0 [ <required> \$a ]\n    Parameter #1 [ <required> \$b ]\n  }\n}"],
            ['Roave\BetterReflectionTest\Fixture\functionWithReturnType', "Function [ <user> function Roave\BetterReflectionTest\Fixture\\functionWithReturnType ] {\n  @@ %s/Fixture/StringCastFunctions.php 13 - 15\n\n  - Parameters [0] {\n  }\n  - Return [ int ]\n}"],
        ];
    }

    /**
     * @dataProvider toStringProvider
     */
    public function testToString(string $functionName, string $expectedString): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastFunctions.php', $this->astLocator));
        $functionReflection = $reflector->reflectFunction($functionName);

        self::assertStringMatchesFormat($expectedString, (string) $functionReflection);
    }
}
