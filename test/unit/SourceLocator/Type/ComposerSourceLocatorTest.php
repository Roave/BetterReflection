<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Type;

use ClassWithNoNamespace;
use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Type\ComposerSourceLocator
 */
class ComposerSourceLocatorTest extends TestCase
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
        $fileName  = __DIR__ . '/../../Fixture/NoNamespace.php';

        $loader = $this->createMock(ClassLoader::class);

        $loader
            ->expects($this->once())
            ->method('findFile')
            ->with($className)
            ->will($this->returnValue($fileName));

        /** @var ClassLoader $loader */
        $locator = new ComposerSourceLocator($loader, $this->astLocator);

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
        $locator = new ComposerSourceLocator($loader, $this->astLocator);

        self::assertNull($locator->locateIdentifier($this->getMockReflector(), new Identifier(
            $className,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        )));
    }

    public function testInvokeThrowsExceptionWhenTryingToLocateFunction() : void
    {
        $loader = $this->createMock(ClassLoader::class);

        /** @var ClassLoader $loader */
        $locator = new ComposerSourceLocator($loader, $this->astLocator);

        self::assertNull($locator->locateIdentifier($this->getMockReflector(), new Identifier(
            'foo',
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
        )));
    }
}
