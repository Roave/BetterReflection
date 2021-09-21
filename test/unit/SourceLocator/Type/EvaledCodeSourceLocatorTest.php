<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\EvaledLocatedSource;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function assert;
use function uniqid;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator
 */
class EvaledCodeSourceLocatorTest extends TestCase
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

    private function getMockReflector(): Reflector|MockObject
    {
        return $this->createMock(Reflector::class);
    }

    public function testCanReflectEvaledClass(): void
    {
        $className = uniqid('foo', false);

        eval('class ' . $className . ' {function foo(){}}');

        $locator = new EvaledCodeSourceLocator($this->astLocator, $this->sourceStubber);

        $reflection = $locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
        );
        assert($reflection instanceof ReflectionClass);
        $source = $reflection->getLocatedSource();

        self::assertInstanceOf(EvaledLocatedSource::class, $source);
        self::assertStringMatchesFormat('%Aclass%A' . $className . '%A', $source->getSource());
    }

    public function testCanReflectEvaledInterface(): void
    {
        $interfaceName = uniqid('foo', false);

        eval('interface ' . $interfaceName . ' {function foo();}');

        $locator = new EvaledCodeSourceLocator($this->astLocator, $this->sourceStubber);

        $reflection = $locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier($interfaceName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
        );

        self::assertInstanceOf(EvaledLocatedSource::class, $reflection->getLocatedSource());
        self::assertStringMatchesFormat('%Ainterface%A' . $interfaceName . '%A', $reflection->getLocatedSource()->getSource());
    }

    public function testCanReflectEvaledTrait(): void
    {
        $traitName = uniqid('foo', false);

        eval('trait ' . $traitName . ' {function foo(){}}');

        $locator = new EvaledCodeSourceLocator($this->astLocator, $this->sourceStubber);

        $reflection = $locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier($traitName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
        );

        self::assertInstanceOf(EvaledLocatedSource::class, $reflection->getLocatedSource());
        self::assertStringMatchesFormat('%Atrait%A' . $traitName . '%A', $reflection->getLocatedSource()->getSource());
    }

    public function testCanReflectEvaledLocatedSourceClass(): void
    {
        $reflector = new ClassReflector(new EvaledCodeSourceLocator($this->astLocator, $this->sourceStubber));
        $className = uniqid('foo', false);

        eval('class ' . $className . ' {function foo($bar = "baz") {}}');

        $class = $reflector->reflect($className);

        self::assertInstanceOf(ReflectionClass::class, $class);
        self::assertSame($className, $class->getName());
        self::assertFalse($class->isInternal());
        self::assertTrue($class->isUserDefined());
        self::assertNull($class->getFileName());
        self::assertCount(1, $class->getMethods());
    }

    public function testCannotReflectRequiredClass(): void
    {
        self::assertNull(
            (new EvaledCodeSourceLocator($this->astLocator, $this->sourceStubber))
                ->locateIdentifier($this->getMockReflector(), new Identifier(self::class, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))),
        );
    }

    public function testReturnsNullForNonExistentCode(): void
    {
        $locator = new EvaledCodeSourceLocator($this->astLocator, $this->sourceStubber);
        self::assertNull(
            $locator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier(
                    'Foo\Bar',
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                ),
            ),
        );
    }

    public function testReturnsNullForFunctions(): void
    {
        $locator = new EvaledCodeSourceLocator($this->astLocator, $this->sourceStubber);
        self::assertNull(
            $locator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier(
                    'foo',
                    new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
                ),
            ),
        );
    }

    public function testReturnsNullForMissingStub(): void
    {
        $sourceStubber = $this->createMock(SourceStubber::class);
        $sourceStubber
            ->method('generateClassStub')
            ->with(CoreReflectionClass::class)
            ->willReturn(null);

        $sourceLocator = new EvaledCodeSourceLocator($this->astLocator, $sourceStubber);

        self::assertNull(
            $sourceLocator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier(
                    CoreReflectionClass::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                ),
            ),
        );
    }
}
