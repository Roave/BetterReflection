<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Exception\NotAClassReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\Fixture;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\NotAClassReflection
 */
class NotAClassReflectionTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = (new BetterReflection())->astLocator();
    }

    public function testFromInterface() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php', $this->astLocator));
        $exception = NotAClassReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleInterface::class));

        self::assertInstanceOf(NotAClassReflection::class, $exception);
        self::assertSame(
            'Provided node "' . Fixture\ExampleInterface::class . '" is not class, but "interface"',
            $exception->getMessage()
        );
    }

    public function testFromTrait() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php', $this->astLocator));
        $exception = NotAClassReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleTrait::class));

        self::assertInstanceOf(NotAClassReflection::class, $exception);
        self::assertSame(
            'Provided node "' . Fixture\ExampleTrait::class . '" is not class, but "trait"',
            $exception->getMessage()
        );
    }
}
