<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflection\StringCast;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;
use Rector\BetterReflectionTest\Fixture\StringCastProperties;

/**
 * @covers \Rector\BetterReflection\Reflection\StringCast\ReflectionPropertyStringCast
 */
class ReflectionPropertyStringCastTest extends TestCase
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
            ['publicProperty', 'Property [ <default> public $publicProperty ]'],
            ['protectedProperty', 'Property [ <default> protected $protectedProperty ]'],
            ['privateProperty', 'Property [ <default> private $privateProperty ]'],
            ['publicStaticProperty', 'Property [ public static $publicStaticProperty ]'],
        ];
    }

    /**
     * @param string $propertyName
     * @param string $expectedString
     * @dataProvider toStringProvider
     */
    public function testToString(string $propertyName, string $expectedString) : void
    {
        $reflector       = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastProperties.php', $this->astLocator));
        $classReflection = $reflector->reflect(StringCastProperties::class);

        self::assertSame($expectedString, (string) $classReflection->getProperty($propertyName));
    }
}
