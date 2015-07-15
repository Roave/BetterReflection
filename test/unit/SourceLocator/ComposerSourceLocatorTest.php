<?php

namespace BetterReflectionTest\SourceLocator;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\SourceLocator\ComposerSourceLocator;
use ClassWithNoNamespace;
use Composer\Autoload\ClassLoader;
use LogicException;
use UnexpectedValueException;

/**
 * @covers \BetterReflection\SourceLocator\ComposerSourceLocator
 */
class ComposerSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testInvokableLoadsSource()
    {
        $className = 'ClassWithNoNamespace';
        $fileName = __DIR__ . '/../Fixture/NoNamespace.php';
        $expectedContent = file_get_contents($fileName);

        $loader = $this->getMockBuilder(ClassLoader::class)
            ->setMethods(['findFile'])
            ->getMock();

        $loader
            ->expects($this->once())
            ->method('findFile')
            ->with($className)
            ->will($this->returnValue($fileName));

        /** @var ClassLoader $loader */
        $locator = new ComposerSourceLocator($loader);

        $locatedSource = $locator->__invoke(new Identifier(
            $className,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        ));

        $this->assertSame($expectedContent, $locatedSource->getSource());
        $this->assertSame($fileName, $locatedSource->getFileName());
    }

    public function testInvokableThrowsExceptionWhenClassNotResolved()
    {
        $className = ClassWithNoNamespace::class;

        $loader = $this->getMockBuilder(ClassLoader::class)
            ->setMethods(['findFile'])
            ->getMock();

        $loader
            ->expects($this->once())
            ->method('findFile')
            ->with($className)
            ->will($this->returnValue(null));

        /** @var ClassLoader $loader */
        $locator = new ComposerSourceLocator($loader);

        $this->setExpectedException(UnexpectedValueException::class);
        $locator->__invoke(new Identifier(
            $className,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        ));
    }

    public function testInvokeThrowsExceptionWhenTryingToLocateFunction()
    {
        $loader = $this->getMock(ClassLoader::class);

        /** @var ClassLoader $loader */
        $locator = new ComposerSourceLocator($loader);

        $this->setExpectedException(LogicException::class);
        $locator->__invoke(new Identifier(
            'foo',
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
        ));
    }
}
