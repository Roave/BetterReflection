<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use ClassWithNoNamespace;
use Composer\Autoload\ClassLoader;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator
 */
class ComposerSourceLocatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    public function testInvokableLoadsSource() : void
    {
        $className = ClassWithNoNamespace::class;
        $fileName = __DIR__ . '/../../Fixture/NoNamespace.php';

        $loader = $this->createMock(ClassLoader::class);

        $loader
            ->expects($this->once())
            ->method('findFile')
            ->with($className)
            ->will($this->returnValue($fileName));

        /** @var ClassLoader $loader */
        $locator = new ComposerSourceLocator($loader);

        $reflectionClass = $locator->locateIdentifier($this->getMockReflector(), new Identifier(
            $className,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        ));

        self::assertSame('ClassWithNoNamespace', $reflectionClass->getName());
    }

    public function testInvokableThrowsExceptionWhenClassNotResolved() : void
    {
        $className = ClassWithNoNamespace::class;

        $loader = $this->createMock(ClassLoader::class);

        $loader
            ->expects($this->once())
            ->method('findFile')
            ->with($className)
            ->will($this->returnValue(null));

        /** @var ClassLoader $loader */
        $locator = new ComposerSourceLocator($loader);

        self::assertNull($locator->locateIdentifier($this->getMockReflector(), new Identifier(
            $className,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        )));
    }

    public function testInvokeThrowsExceptionWhenTryingToLocateFunction() : void
    {
        $loader = $this->createMock(ClassLoader::class);

        /** @var ClassLoader $loader */
        $locator = new ComposerSourceLocator($loader);

        self::assertNull($locator->locateIdentifier($this->getMockReflector(), new Identifier(
            'foo',
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
        )));
    }
}
