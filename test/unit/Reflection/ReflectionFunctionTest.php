<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use Closure;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Exception\FunctionDoesNotExist;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\Attr;
use Roave\BetterReflectionTest\Fixture\ClassWithStaticMethod;
use stdClass;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionFunction
 */
class ReflectionFunctionTest extends TestCase
{
    private Reflector $reflector;

    private Locator $astLocator;

    private SourceStubber $sourceStubber;

    protected function setUp(): void
    {
        parent::setUp();

        $configuration       = BetterReflectionSingleton::instance();
        $this->reflector     = $configuration->reflector();
        $this->astLocator    = $configuration->astLocator();
        $this->sourceStubber = $configuration->sourceStubber();
    }

    public function testNameMethodsWithNoNamespace(): void
    {
        $php = '<?php function foo() {}';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertFalse($function->inNamespace());
        self::assertSame('foo', $function->getName());
        self::assertSame('', $function->getNamespaceName());
        self::assertSame('foo', $function->getShortName());
    }

    public function testNameMethodsInNamespace(): void
    {
        $php = '<?php namespace A\B { function foo() {} }';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('A\B\foo');

        self::assertTrue($function->inNamespace());
        self::assertSame('A\B\foo', $function->getName());
        self::assertSame('A\B', $function->getNamespaceName());
        self::assertSame('foo', $function->getShortName());
    }

    public function testNameMethodsInExplicitGlobalNamespace(): void
    {
        $php = '<?php namespace { function foo() {} }';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertFalse($function->inNamespace());
        self::assertSame('foo', $function->getName());
        self::assertSame('', $function->getNamespaceName());
        self::assertSame('foo', $function->getShortName());
    }

    public function testIsDisabled(): void
    {
        $php = '<?php function foo() {}';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertFalse($function->isDisabled());
    }

    public function testIsUserDefined(): void
    {
        $php = '<?php function foo() {}';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertTrue($function->isUserDefined());
        self::assertFalse($function->isInternal());
        self::assertNull($function->getExtensionName());
    }

    public function testIsInternal(): void
    {
        $reflector = new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber));
        $function  = $reflector->reflectFunction('min');

        self::assertTrue($function->isInternal());
        self::assertFalse($function->isUserDefined());
        self::assertSame('standard', $function->getExtensionName());
    }

    public function testStaticCreationFromName(): void
    {
        require_once __DIR__ . '/../Fixture/Functions.php';
        $reflection = ReflectionFunction::createFromName('Roave\BetterReflectionTest\Fixture\myFunction');
        self::assertSame('myFunction', $reflection->getShortName());
    }

    public function testCreateFromClosure(): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.RequireArrowFunction
        $myClosure = static function () {
            return 5;
        };
        // phpcs:enable
        $reflection = ReflectionFunction::createFromClosure($myClosure);
        self::assertSame(ReflectionFunction::CLOSURE_NAME, $reflection->getShortName());
    }

    public function testCreateFromClosureCanReflectTypeHints(): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.RequireArrowFunction
        $myClosure = static function (stdClass $theParam): int {
            return 5;
        };
        // phpcs:enable
        $reflection = ReflectionFunction::createFromClosure($myClosure);

        $theParam = $reflection->getParameter('theParam')->getClass();
        self::assertSame(stdClass::class, $theParam->getName());
    }

    public function testCreateFromClosureCanReflectTypesInNamespace(): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.RequireArrowFunction
        $myClosure = static function (ClassWithStaticMethod $theParam): int {
            return 5;
        };
        // phpcs:enable
        $reflection = ReflectionFunction::createFromClosure($myClosure);

        $theParam = $reflection->getParameter('theParam')->getClass();
        self::assertSame(ClassWithStaticMethod::class, $theParam->getName());
    }

    public function testCreateFromClosureWithArrowFunction(): void
    {
        $myClosure = static fn (): int => 5;

        $reflection = ReflectionFunction::createFromClosure($myClosure);

        self::assertSame(ReflectionFunction::CLOSURE_NAME, $reflection->getShortName());
    }

    public function testCreateFromClosureWithArrowFunctionCanReflectTypeHints(): void
    {
        $myClosure = static fn (stdClass $theParam): int => 5;

        $reflection = ReflectionFunction::createFromClosure($myClosure);

        $theParam = $reflection->getParameter('theParam')->getClass();
        self::assertSame(stdClass::class, $theParam->getName());
    }

    public function testCreateFromClosureWithArrowFunctionCanReflectTypesInNamespace(): void
    {
        $myClosure = static fn (ClassWithStaticMethod $theParam): int => 5;

        $reflection = ReflectionFunction::createFromClosure($myClosure);

        $theParam = $reflection->getParameter('theParam')->getClass();
        self::assertSame(ClassWithStaticMethod::class, $theParam->getName());
    }

    public function testIsStaticFromClosure(): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.RequireArrowFunction
        $closure = static function () {
            return 5;
        };
        // phpcs:enable
        $reflection = ReflectionFunction::createFromClosure($closure);
        self::assertTrue($reflection->isStatic());
    }

    public function testIsNotStaticFromClosure(): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.RequireArrowFunction
        // phpcs:disable SlevomatCodingStandard.Functions.StaticClosure.ClosureNotStatic
        $closure = function () {
            return 5;
        };
        // phpcs:enable
        $reflection = ReflectionFunction::createFromClosure($closure);
        self::assertFalse($reflection->isStatic());
    }

    public function testIsStaticFromArrowFunction(): void
    {
        $closure = static fn () => 5;

        $reflection = ReflectionFunction::createFromClosure($closure);
        self::assertTrue($reflection->isStatic());
    }

    public function testIsNotStaticFromArrowFunction(): void
    {
        // phpcs:disable SlevomatCodingStandard.Functions.StaticClosure.ClosureNotStatic
        $closure = fn () => 5;
        // phpcs:enable

        $reflection = ReflectionFunction::createFromClosure($closure);
        self::assertFalse($reflection->isStatic());
    }

    public function testToString(): void
    {
        require_once __DIR__ . '/../Fixture/Functions.php';
        $functionInfo = ReflectionFunction::createFromName('Roave\BetterReflectionTest\Fixture\myFunction');

        self::assertStringMatchesFormat("Function [ <user> function Roave\BetterReflectionTest\Fixture\myFunction ] {\n  @@ %s/test/unit/Fixture/Functions.php 5 - 6\n}", (string) $functionInfo);
    }

    public function testGetClosure(): void
    {
        require_once __DIR__ . '/../Fixture/Functions.php';

        $functionReflection = ReflectionFunction::createFromName('Roave\BetterReflectionTest\Fixture\myFunctionWithParams');

        $closure = $functionReflection->getClosure();

        self::assertInstanceOf(Closure::class, $closure);
        self::assertSame(5, $closure(2, 3));
    }

    public function testGetClosureThrowsExceptionWhenFunctionIsClosure(): void
    {
        $closure = static function (): void {
        };

        $functionReflection = ReflectionFunction::createFromClosure($closure);

        $this->expectException(NotImplemented::class);

        $functionReflection->getClosure();
    }

    public function testGetClosureThrowsExceptionWhenFunctionDoesNotExist(): void
    {
        $php = '<?php function foo() {}';

        $reflector          = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $functionReflection = $reflector->reflectFunction('foo');

        $this->expectException(FunctionDoesNotExist::class);

        $functionReflection->getClosure();
    }

    public function testInvoke(): void
    {
        require_once __DIR__ . '/../Fixture/Functions.php';

        $functionReflection = ReflectionFunction::createFromName('Roave\BetterReflectionTest\Fixture\myFunctionWithParams');

        self::assertSame(5, $functionReflection->invoke(2, 3));
        self::assertSame(10, $functionReflection->invokeArgs([3, 7]));
    }

    public function testInvokeThrowsExceptionWhenFunctionIsClosure(): void
    {
        $closure = static function (): void {
        };

        $functionReflection = ReflectionFunction::createFromClosure($closure);

        $this->expectException(NotImplemented::class);

        $functionReflection->invoke();
    }

    public function testInvokeArgsThrowsExceptionWhenFunctionIsClosure(): void
    {
        $closure = static function (): void {
        };

        $functionReflection = ReflectionFunction::createFromClosure($closure);

        $this->expectException(NotImplemented::class);

        $functionReflection->invokeArgs();
    }

    public function testInvokeThrowsExceptionWhenFunctionDoesNotExist(): void
    {
        $php = '<?php function foo() {}';

        $reflector          = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $functionReflection = $reflector->reflectFunction('foo');

        $this->expectException(FunctionDoesNotExist::class);

        $functionReflection->invoke();
    }

    public function testInvokeArgsThrowsExceptionWhenFunctionDoesNotExist(): void
    {
        $php = '<?php function foo() {}';

        $reflector          = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $functionReflection = $reflector->reflectFunction('foo');

        $this->expectException(FunctionDoesNotExist::class);

        $functionReflection->invokeArgs();
    }

    public function testGetAttributesWithoutAttributes(): void
    {
        $php = '<?php function foo() {}';

        $reflector          = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $functionReflection = $reflector->reflectFunction('foo');
        $attributes         = $functionReflection->getAttributes();

        self::assertCount(0, $attributes);
    }

    public function testGetAttributesWithAttributes(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $functionReflection = $reflector->reflectFunction('Roave\BetterReflectionTest\Fixture\functionWithAttributes');
        $attributes         = $functionReflection->getAttributes();

        self::assertCount(2, $attributes);
    }

    public function testGetAttributesByName(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $functionReflection = $reflector->reflectFunction('Roave\BetterReflectionTest\Fixture\functionWithAttributes');
        $attributes         = $functionReflection->getAttributesByName(Attr::class);

        self::assertCount(1, $attributes);
    }

    public function testGetAttributesByInstance(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $functionReflection = $reflector->reflectFunction('Roave\BetterReflectionTest\Fixture\functionWithAttributes');
        $attributes         = $functionReflection->getAttributesByInstance(Attr::class);

        self::assertCount(2, $attributes);
    }
}
