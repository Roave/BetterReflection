<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\NotATraitReflection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture;

/** @covers \Roave\BetterReflection\Reflection\Exception\NotATraitReflection */
class NotATraitReflectionTest extends TestCase
{
    private Locator $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function testFromClass(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php', $this->astLocator));
        $exception = NotATraitReflection::fromReflectionClass($reflector->reflectClass(Fixture\ExampleClass::class));

        self::assertInstanceOf(NotATraitReflection::class, $exception);
        self::assertSame(
            'Provided node "' . Fixture\ExampleClass::class . '" is not trait, but "class"',
            $exception->getMessage(),
        );
    }

    public function testFromInterface(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php', $this->astLocator));
        $exception = NotATraitReflection::fromReflectionClass($reflector->reflectClass(Fixture\ExampleInterface::class));

        self::assertInstanceOf(NotATraitReflection::class, $exception);
        self::assertSame(
            'Provided node "' . Fixture\ExampleInterface::class . '" is not trait, but "interface"',
            $exception->getMessage(),
        );
    }
}
