<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Reflection;

use ClassWithoutNamespaceForSourceStubber;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use Rector\BetterReflection\SourceLocator\Reflection\SourceStubber;
use Rector\BetterReflectionTest\Fixture\ClassForSourceStubber;
use Rector\BetterReflectionTest\Fixture\EmptyTrait;
use Rector\BetterReflectionTest\Fixture\InterfaceForSourceStubber;
use Rector\BetterReflectionTest\Fixture\TraitForSourceStubber;
use Traversable;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Reflection\SourceStubber
 */
class SourceStubberTest extends TestCase
{
    /**
     * @var SourceStubber
     */
    private $stubber;

    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->stubber = new SourceStubber();
    }

    public function testCanStubClass() : void
    {
        self::assertStringMatchesFormat(
            '%Aclass stdClass%A{%A}%A',
            $this->stubber->__invoke(new CoreReflectionClass('stdClass'))
        );
    }

    public function testCanStubInterface() : void
    {
        self::assertStringMatchesFormat(
            '%Ainterface Traversable%A{%A}%A',
            $this->stubber->__invoke(new CoreReflectionClass(Traversable::class))
        );
    }

    public function testCanStubTraits() : void
    {
        self::assertStringMatchesFormat(
            '%Atrait EmptyTrait%A{%A}%A',
            $this->stubber->__invoke(new CoreReflectionClass(EmptyTrait::class))
        );
    }

    public function testClassStub() : void
    {
        $classReflection = new CoreReflectionClass(ClassForSourceStubber::class);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/ClassForSourceStubberExpected.php', "<?php\n" . $this->stubber->__invoke($classReflection) . "\n");
    }

    public function testClassWithoutNamespaceStub() : void
    {
        require __DIR__ . '/../../Fixture/ClassWithoutNamespaceForSourceStubber.php';
        $classReflection = new CoreReflectionClass(ClassWithoutNamespaceForSourceStubber::class);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/ClassWithoutNamespaceForSourceStubberExpected.php', "<?php\n" . $this->stubber->__invoke($classReflection) . "\n");
    }

    public function testInterfaceStub() : void
    {
        $classReflection = new CoreReflectionClass(InterfaceForSourceStubber::class);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/InterfaceForSourceStubberExpected.php', "<?php\n" . $this->stubber->__invoke($classReflection) . "\n");
    }

    public function testTraitStub() : void
    {
        $classReflection = new CoreReflectionClass(TraitForSourceStubber::class);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/TraitForSourceStubberExpected.php', "<?php\n" . $this->stubber->__invoke($classReflection) . "\n");
    }
}
