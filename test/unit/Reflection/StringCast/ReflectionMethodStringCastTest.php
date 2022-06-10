<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use Exception;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\StringCastMethods;

/**
 * @covers \Roave\BetterReflection\Reflection\StringCast\ReflectionMethodStringCast
 */
class ReflectionMethodStringCastTest extends TestCase
{
    private Locator $astLocator;

    private SourceStubber $sourceStubber;

    protected function setUp(): void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->astLocator    = $betterReflection->astLocator();
        $this->sourceStubber = $betterReflection->sourceStubber();
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public function toStringProvider(): array
    {
        return [
            ['__construct', "Method [ <user, ctor> public method __construct ] {\n  @@ %s/Fixture/StringCastMethods.php 23 - 25\n}"],
            ['__destruct', "Method [ <user, dtor> public method __destruct ] {\n  @@ %s/Fixture/StringCastMethods.php 27 - 29\n}"],
            ['publicMethod', "Method [ <user> public method publicMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 31 - 33\n}"],
            ['protectedMethod', "Method [ <user> protected method protectedMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 35 - 37\n}"],
            ['privateMethod', "Method [ <user> private method privateMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 39 - 41\n}"],
            ['finalPublicMethod', "Method [ <user> final public method finalPublicMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 43 - 45\n}"],
            ['abstractPublicMethod', "Method [ <user> abstract public method abstractPublicMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 47 - 47\n}"],
            ['staticPublicMethod', "Method [ <user> static public method staticPublicMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 49 - 51\n}"],
            ['noVisibility', "Method [ <user> public method noVisibility ] {\n  @@ %s/Fixture/StringCastMethods.php 53 - 55\n}"],
            ['overwrittenMethod', "Method [ <user, overwrites Roave\BetterReflectionTest\Fixture\StringCastMethodsParent, prototype Roave\BetterReflectionTest\Fixture\StringCastMethodsParent> public method overwrittenMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 57 - 59\n}"],
            ['inheritedMethod', "Method [ <user, inherits Roave\BetterReflectionTest\Fixture\StringCastMethodsParent> public method inheritedMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 16 - 18\n}"],
            ['prototypeMethod', "Method [ <user, prototype Roave\BetterReflectionTest\Fixture\StringCastMethodsInterface> public method prototypeMethod ] {\n  @@ %s/Fixture/StringCastMethods.php 61 - 63\n}"],
            ['methodWithParameters', "Method [ <user> public method methodWithParameters ] {\n  @@ %s/Fixture/StringCastMethods.php 65 - 67\n\n  - Parameters [2] {\n    Parameter #0 [ <required> \$a ]\n    Parameter #1 [ <required> \$b ]\n  }\n}"],
            ['methodWithReturnType', "Method [ <user> public method methodWithReturnType ] {\n  @@ %s/Fixture/StringCastMethods.php 69 - 71\n\n  - Parameters [0] {\n  }\n  - Return [ string ]\n}"],
        ];
    }

    /**
     * @dataProvider toStringProvider
     */
    public function testToString(string $methodName, string $expectedString): void
    {
        $reflector       = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastMethods.php', $this->astLocator));
        $classReflection = $reflector->reflectClass(StringCastMethods::class);

        self::assertStringMatchesFormat($expectedString, (string) $classReflection->getMethod($methodName));
    }

    public function testToStringForInternal(): void
    {
        // phpcs:disable SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly.ReferencedGeneralException
        $classReflection = (new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber)))->reflectClass(Exception::class);
        // phpcs:enable

        self::assertSame("Method [ <internal:Core, prototype Throwable> final public method getMessage ] {\n\n  - Parameters [0] {\n  }\n  - Return [ string ]\n}", (string) $classReflection->getMethod('getMessage'));
    }
}
