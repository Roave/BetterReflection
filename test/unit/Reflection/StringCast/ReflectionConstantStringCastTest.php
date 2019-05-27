<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Roave\BetterReflection\Reflection\StringCast\ReflectionConstantStringCast
 */
class ReflectionConstantStringCastTest extends TestCase
{
    /** @var Locator */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function toStringProvider() : array
    {
        return [
            ['Roave\BetterReflectionTest\Fixture\BY_CONST', "Constant [ <user> boolean Roave\BetterReflectionTest\Fixture\BY_CONST ] {\n  @@ %s/Fixture/StringCastConstants.php 5 - 5\n 1 }"],
            ['Roave\BetterReflectionTest\Fixture\BY_CONST_1', "Constant [ <user> integer Roave\BetterReflectionTest\Fixture\BY_CONST_1 ] {\n  @@ %s/Fixture/StringCastConstants.php 6 - 7\n 1 }"],
            ['Roave\BetterReflectionTest\Fixture\BY_CONST_2', "Constant [ <user> integer Roave\BetterReflectionTest\Fixture\BY_CONST_2 ] {\n  @@ %s/Fixture/StringCastConstants.php 6 - 7\n 2 }"],
            ['BY_DEFINE', "Constant [ <user> string BY_DEFINE ] {\n  @@ %s/Fixture/StringCastConstants.php 9 - 9\n define }"],
            ['E_ALL', 'Constant [ <internal:Core> integer E_ALL ] { %d }'],
        ];
    }

    /**
     * @dataProvider toStringProvider
     */
    public function testToString(string $constantName, string $expectedString) : void
    {
        $sourceLocator = new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastConstants.php', $this->astLocator),
            new PhpInternalSourceLocator($this->astLocator),
        ]);

        $reflector          = new ConstantReflector($sourceLocator, new ClassReflector($sourceLocator));
        $constantReflection = $reflector->reflect($constantName);

        self::assertStringMatchesFormat($expectedString, (string) $constantReflection);
    }
}
