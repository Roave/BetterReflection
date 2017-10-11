<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflection\StringCast;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\Reflector\FunctionReflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Rector\BetterReflection\Reflection\StringCast\ReflectionFunctionStringCast
 */
class ReflectionFunctionStringCastTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    /**
     * @var ClassReflector
     */
    private $classReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->astLocator     = $betterReflection->astLocator();
        $this->classReflector = $betterReflection->classReflector();
    }

    public function toStringProvider() : array
    {
        return [
            ['Rector\BetterReflectionTest\Fixture\functionWithoutParameters', "Function [ <user> function Rector\BetterReflectionTest\Fixture\\functionWithoutParameters ] {\n  @@ %s/Fixture/StringCastFunctions.php 5 - 7\n}"],
            ['Rector\BetterReflectionTest\Fixture\functionWithParameters', "Function [ <user> function Rector\BetterReflectionTest\Fixture\\functionWithParameters ] {\n  @@ %s/Fixture/StringCastFunctions.php 9 - 11\n\n  - Parameters [2] {\n    Parameter #0 [ <required> \$a ]\n    Parameter #1 [ <required> \$b ]\n  }\n}"],
        ];
    }

    /**
     * @param string $functionName
     * @param string $expectedString
     * @dataProvider toStringProvider
     */
    public function testToString(string $functionName, string $expectedString) : void
    {
        $reflector          = new FunctionReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastFunctions.php', $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect($functionName);

        self::assertStringMatchesFormat($expectedString, (string) $functionReflection);
    }
}
