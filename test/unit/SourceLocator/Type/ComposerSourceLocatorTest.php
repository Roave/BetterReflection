<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use ClassWithNoNamespace;
use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/** @covers \Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator */
class ComposerSourceLocatorTest extends TestCase
{
    private Locator $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    private function getMockReflector(): Reflector|MockObject
    {
        return $this->createMock(Reflector::class);
    }

    public function testInvokableLoadsSource(): void
    {
        $className = ClassWithNoNamespace::class;
        $fileName  = __DIR__ . '/../../Fixture/NoNamespace.php';

        $loader = $this->createMock(ClassLoader::class);

        $loader
            ->expects($this->once())
            ->method('findFile')
            ->with($className)
            ->will($this->returnValue($fileName));

        $locator = new ComposerSourceLocator($loader, $this->astLocator);

        $reflectionClass = $locator->locateIdentifier($this->getMockReflector(), new Identifier(
            $className,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
        ));

        self::assertSame('ClassWithNoNamespace', $reflectionClass->getName());
    }

    public function testInvokableThrowsExceptionWhenClassNotResolved(): void
    {
        $className = ClassWithNoNamespace::class;

        $loader = $this->createMock(ClassLoader::class);

        $loader
            ->expects($this->once())
            ->method('findFile')
            ->with($className)
            ->will($this->returnValue(null));

        $locator = new ComposerSourceLocator($loader, $this->astLocator);

        self::assertNull($locator->locateIdentifier($this->getMockReflector(), new Identifier(
            $className,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
        )));
    }

    public function testInvokeThrowsExceptionWhenTryingToLocateFunction(): void
    {
        $loader = $this->createMock(ClassLoader::class);

        $locator = new ComposerSourceLocator($loader, $this->astLocator);

        self::assertNull($locator->locateIdentifier($this->getMockReflector(), new Identifier(
            'foo',
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
        )));
    }
}
