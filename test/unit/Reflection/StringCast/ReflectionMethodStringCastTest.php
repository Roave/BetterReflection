<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use Exception;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\StringCastMethods;

/**
 * @covers \Roave\BetterReflection\Reflection\StringCast\ReflectionMethodStringCast
 */
class ReflectionMethodStringCastTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    protected function setUp() : void
    {
        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function toStringProvider() : array
    {
        return [
            ['__construct', "Method [ <user, ctor> public method __construct ] {\n  @@ %s/Fixture/StringCastMethods.php 19 - 21\n}"],
            ['__destruct', "Method [ <user, dtor> public method __destruct ] {\n  @@ %s/Fixture/StringCastMethods.php 23 - 25\n}"],
            ['publicMethod', "Method [ <user> public method publicMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 27 - 29\n}"],
            ['protectedMethod', "Method [ <user> protected method protectedMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 31 - 33\n}"],
            ['privateMethod', "Method [ <user> private method privateMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 35 - 37\n}"],
            ['finalPublicMethod', "Method [ <user> final public method finalPublicMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 39 - 41\n}"],
            ['abstractPublicMethod', "Method [ <user> abstract public method abstractPublicMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 43 - 43\n}"],
            ['staticPublicMethod', "Method [ <user> static public method staticPublicMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 45 - 47\n}"],
            ['noVisibility', "Method [ <user> public method noVisibility ] {\n  @@ %s/Fixture/StringCastMethods.php 49 - 51\n}"],
            ['overwrittenMethod', "Method [ <user, overwrites Roave\BetterReflectionTest\Fixture\StringCastMethodsParent, prototype Roave\BetterReflectionTest\Fixture\StringCastMethodsParent> public method overwrittenMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 53 - 55\n}"],
            ['prototypeMethod', "Method [ <user, prototype Roave\BetterReflectionTest\Fixture\StringCastMethodsInterface> public method prototypeMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 57 - 59\n}"],
            ['methodWithParameters', "Method [ <user> public method methodWithParameters ] {\n  @@ %s/Fixture/StringCastMethods.php 61 - 63\n\n  - Parameters [2] {\n    Parameter #0 [ <required> \$a ]\n    Parameter #1 [ <required> \$b ]\n  }\n}"],
        ];
    }

    /**
     * @param string $methodName
     * @param string $expectedString
     * @dataProvider toStringProvider
     */
    public function testToString(string $methodName, string $expectedString) : void
    {
        $reflector       = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastMethods.php', $this->astLocator));
        $classReflection = $reflector->reflect(StringCastMethods::class);

        self::assertStringMatchesFormat($expectedString, (string) $classReflection->getMethod($methodName));
    }

    public function testToStringForInternal() : void
    {
        $classReflection = (new ClassReflector(new PhpInternalSourceLocator($this->astLocator)))->reflect(Exception::class);

        self::assertSame("Method [ <internal:Core, prototype Throwable> final public method getMessage ] {\n}", (string) $classReflection->getMethod('getMessage'));
    }
}
