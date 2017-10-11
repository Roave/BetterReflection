<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\Exception\NotAClassReflection;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;
use Rector\BetterReflectionTest\Fixture;

/**
 * @covers \Rector\BetterReflection\Reflection\Exception\NotAClassReflection
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

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
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
