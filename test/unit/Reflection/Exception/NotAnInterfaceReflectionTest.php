<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\Exception\NotAnInterfaceReflection;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;
use Rector\BetterReflectionTest\Fixture;

/**
 * @covers \Rector\BetterReflection\Reflection\Exception\NotAnInterfaceReflection
 */
class NotAnInterfaceReflectionTest extends TestCase
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

    public function testFromClass() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php', $this->astLocator));
        $exception = NotAnInterfaceReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleClass::class));

        self::assertInstanceOf(NotAnInterfaceReflection::class, $exception);
        self::assertSame(
            'Provided node "' . Fixture\ExampleClass::class . '" is not interface, but "class"',
            $exception->getMessage()
        );
    }

    public function testFromTrait() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php', $this->astLocator));
        $exception = NotAnInterfaceReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleTrait::class));

        self::assertInstanceOf(NotAnInterfaceReflection::class, $exception);
        self::assertSame(
            'Provided node "' . Fixture\ExampleTrait::class . '" is not interface, but "trait"',
            $exception->getMessage()
        );
    }
}
