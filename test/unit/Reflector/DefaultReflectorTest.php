<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflector;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Roave\BetterReflection\Reflector\DefaultReflector
 */
class DefaultReflectorTest extends TestCase
{
    private Reflector $reflector;
    private Locator $astLocator;

    public function setUp(): void
    {
        parent::setUp();

        $configuration    = BetterReflectionSingleton::instance();
        $this->reflector  = $configuration->reflector();
        $this->astLocator = $configuration->astLocator();
    }

    public function testReflectClass(): void
    {
        $reflection = $this->createMock(ReflectionClass::class);

        $sourceLocator = $this->createMock(SourceLocator::class);
        $sourceLocator
            ->expects($this->once())
            ->method('locateIdentifier')
            ->willReturn($reflection);

        $reflector = new DefaultReflector($sourceLocator);

        self::assertSame($reflection, $reflector->reflectClass('MyClass'));
    }

    public function testThrowsExceptionWhenClassIdentifierNotFound(): void
    {
        $this->expectException(IdentifierNotFound::class);

        $this->reflector->reflectClass('Something\That\Should\Not\Exist');
    }

    public function testReflectAllClasses(): void
    {
        $classes = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php', $this->astLocator),
        ))->reflectAllClasses();

        self::assertContainsOnlyInstancesOf(ReflectionClass::class, $classes);
        self::assertCount(10, $classes);
    }

    public function testReflectFunction(): void
    {
        $reflection = $this->createMock(ReflectionFunction::class);

        $sourceLocator = $this->createMock(SourceLocator::class);
        $sourceLocator
            ->expects($this->once())
            ->method('locateIdentifier')
            ->willReturn($reflection);

        $reflector = new DefaultReflector($sourceLocator);

        self::assertSame($reflection, $reflector->reflectFunction('foo'));
    }

    public function testThrowsExceptionWhenFunctionIdentifierNotFound(): void
    {
        $this->expectException(IdentifierNotFound::class);

        $this->reflector->reflectFunction('Something\That\Should\not_exist');
    }

    public function testReflectAllFunction(): void
    {
        $functions = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Functions.php', $this->astLocator),
        ))->reflectAllFunctions();

        self::assertContainsOnlyInstancesOf(ReflectionFunction::class, $functions);
        self::assertCount(2, $functions);
    }

    public function testReflectConstant(): void
    {
        $reflection = $this->createMock(ReflectionConstant::class);

        $sourceLocator = $this->createMock(SourceLocator::class);
        $sourceLocator
            ->expects($this->once())
            ->method('locateIdentifier')
            ->willReturn($reflection);

        $reflector = new DefaultReflector($sourceLocator);

        self::assertSame($reflection, $reflector->reflectConstant('FOO'));
    }

    public function testThrowsExceptionWhenConstantIdentifierNotFound(): void
    {
        $this->expectException(IdentifierNotFound::class);

        $this->reflector->reflectConstant('Something\That\Should\NOT_EXIST');
    }

    public function testReflectAllConstants(): void
    {
        $constants = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Constants.php', $this->astLocator),
        ))->reflectAllConstants();

        self::assertContainsOnlyInstancesOf(ReflectionConstant::class, $constants);
        self::assertCount(5, $constants);
    }

    public function testReflectAllAttributes(): void
    {
        $attributes = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator),
        ))->reflectAllAttributes();

        self::assertContainsOnlyInstancesOf(ReflectionAttribute::class, $attributes);
        self::assertCount(24, $attributes);
    }
}
