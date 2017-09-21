<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\StringCastConstants;

/**
 * @covers \Roave\BetterReflection\Reflection\StringCast\ReflectionClassConstantStringCast
 */
class ReflectionClassConstantStringCastTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function toStringProvider() : array
    {
        return [
            ['PUBLIC_CONSTANT', "Constant [ public boolean PUBLIC_CONSTANT ] { 1 }\n"],
            ['PROTECTED_CONSTANT', "Constant [ protected integer PROTECTED_CONSTANT ] { 0 }\n"],
            ['PRIVATE_CONSTANT', "Constant [ private string PRIVATE_CONSTANT ] { string }\n"],
            ['NO_VISIBILITY_CONSTANT', "Constant [ public array NO_VISIBILITY_CONSTANT ] { Array }\n"],
        ];
    }

    /**
     * @param string $constantName
     * @param string $expectedString
     * @dataProvider toStringProvider
     */
    public function testToString(string $constantName, string $expectedString) : void
    {
        $reflector       = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastConstants.php', $this->astLocator));
        $classReflection = $reflector->reflect(StringCastConstants::class);

        self::assertSame($expectedString, (string) $classReflection->getReflectionConstant($constantName));
    }
}
