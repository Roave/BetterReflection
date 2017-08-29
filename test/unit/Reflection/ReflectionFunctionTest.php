<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use phpDocumentor\Reflection\Types\Boolean;
use PHPUnit\Framework\TestCase;
use Reflector;
use Roave\BetterReflection\Configuration;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use stdClass;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionFunction
 */
class ReflectionFunctionTest extends TestCase
{
    /**
     * @var ClassReflector
     */
    private $classReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $this->classReflector = (new Configuration())->classReflector();
    }

    public function testImplementsReflector() : void
    {
        $php = '<?php function foo() {}';

        $reflector    = new FunctionReflector(new StringSourceLocator($php), $this->classReflector);
        $functionInfo = $reflector->reflect('foo');

        self::assertInstanceOf(Reflector::class, $functionInfo);
    }

    public function testNameMethodsWithNoNamespace() : void
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php), $this->classReflector);
        $function  = $reflector->reflect('foo');

        self::assertFalse($function->inNamespace());
        self::assertSame('foo', $function->getName());
        self::assertSame('', $function->getNamespaceName());
        self::assertSame('foo', $function->getShortName());
    }

    public function testNameMethodsInNamespace() : void
    {
        $php = '<?php namespace A\B { function foo() {} }';

        $reflector = new FunctionReflector(new StringSourceLocator($php), $this->classReflector);
        $function  = $reflector->reflect('A\B\foo');

        self::assertTrue($function->inNamespace());
        self::assertSame('A\B\foo', $function->getName());
        self::assertSame('A\B', $function->getNamespaceName());
        self::assertSame('foo', $function->getShortName());
    }

    public function testNameMethodsInExplicitGlobalNamespace() : void
    {
        $php = '<?php namespace { function foo() {} }';

        $reflector = new FunctionReflector(new StringSourceLocator($php), $this->classReflector);
        $function  = $reflector->reflect('foo');

        self::assertFalse($function->inNamespace());
        self::assertSame('foo', $function->getName());
        self::assertSame('', $function->getNamespaceName());
        self::assertSame('foo', $function->getShortName());
    }

    public function testIsDisabled() : void
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php), $this->classReflector);
        $function  = $reflector->reflect('foo');

        self::assertFalse($function->isDisabled());
    }

    public function testIsUserDefined() : void
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php), $this->classReflector);
        $function  = $reflector->reflect('foo');

        self::assertTrue($function->isUserDefined());
    }

    public function testStaticCreationFromName() : void
    {
        require_once __DIR__ . '/../Fixture/Functions.php';
        $reflection = ReflectionFunction::createFromName('Roave\BetterReflectionTest\Fixture\myFunction');
        self::assertSame('myFunction', $reflection->getShortName());
    }

    public function testCreateFromClosure() : void
    {
        $myClosure = function () {
            return 5;
        };
        $reflection = ReflectionFunction::createFromClosure($myClosure);
        self::assertSame(ReflectionFunction::CLOSURE_NAME, $reflection->getShortName());
    }

    public function testCreateFromClosureCanReflectTypeHints() : void
    {
        $myClosure = function (stdClass $theParam) : int {
            return 5;
        };
        $reflection = ReflectionFunction::createFromClosure($myClosure);

        $theParam = $reflection->getParameter('theParam')->getClass();
        self::assertSame('stdClass', $theParam->getName());
    }

    public function functionStringRepresentations() : array
    {
        return [
            ['Roave\BetterReflectionTest\Fixture\myFunction', "Function [ <user> function Roave\BetterReflectionTest\Fixture\myFunction ] {\n  @@ %s/test/unit/Fixture/Functions.php 5 - 6\n}"],
            ['Roave\BetterReflectionTest\Fixture\myFunctionWithParams', "Function [ <user> function Roave\BetterReflectionTest\Fixture\myFunctionWithParams ] {\n  @@ %s/test/unit/Fixture/Functions.php 8 - 9\n\n  - Parameters [2] {\n    Parameter #0 [ <required> \$a ]\n    Parameter #1 [ <required> \$b ]\n  }\n}"],
        ];
    }

    /**
     * @param string $functionName
     * @param string $expectedStringValue
     * @dataProvider functionStringRepresentations
     */
    public function testStringCast(string $functionName, string $expectedStringValue) : void
    {
        require_once __DIR__ . '/../Fixture/Functions.php';
        $functionInfo = ReflectionFunction::createFromName($functionName);

        self::assertStringMatchesFormat($expectedStringValue, (string) $functionInfo);
    }

    public function testGetDocBlockReturnTypes() : void
    {
        $php = '<?php
            /**
             * @return bool
             */
            function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php), $this->classReflector);
        $function  = $reflector->reflect('foo');

        $types = $function->getDocBlockReturnTypes();

        self::assertInternalType('array', $types);
        self::assertCount(1, $types);
        self::assertInstanceOf(Boolean::class, $types[0]);
    }
}
