<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;
use Rector\BetterReflection\SourceLocator\Type\AbstractSourceLocator;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Type\AbstractSourceLocator
 */
class AbstractSourceLocatorTest extends TestCase
{
    public function testLocateIdentifierCallsFindReflection() : void
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php class Foo{}', null);

        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $mockReflection = $this->createMock(ReflectionClass::class);

        /** @var AstLocator|\PHPUnit_Framework_MockObject_MockObject $astLocator */
        $astLocator = $this->createMock(AstLocator::class);

        $astLocator->expects($this->once())
            ->method('findReflection')
            ->with($mockReflector, $locatedSource, $identifier)
            ->will($this->returnValue($mockReflection));

        /** @var AbstractSourceLocator|\PHPUnit_Framework_MockObject_MockObject $sourceLocator */
        $sourceLocator = $this->getMockBuilder(AbstractSourceLocator::class)
            ->setConstructorArgs([$astLocator])
            ->setMethods(['createLocatedSource'])
            ->getMock();

        $sourceLocator->expects($this->once())
            ->method('createLocatedSource')
            ->with($identifier)
            ->will($this->returnValue($locatedSource));

        self::assertSame($mockReflection, $sourceLocator->locateIdentifier($mockReflector, $identifier));
    }

    public function testLocateIdentifierReturnsNullWithoutTryingToFindReflectionWhenUnableToLocateSource() : void
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->createMock(Reflector::class);

        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        /** @var AstLocator|\PHPUnit_Framework_MockObject_MockObject $astLocator */
        $astLocator = $this->createMock(AstLocator::class);

        $astLocator->expects($this->never())
            ->method('findReflection');

        /** @var AbstractSourceLocator|\PHPUnit_Framework_MockObject_MockObject $sourceLocator */
        $sourceLocator = $this->getMockBuilder(AbstractSourceLocator::class)
            ->setConstructorArgs([$astLocator])
            ->setMethods(['createLocatedSource'])
            ->getMock();

        $sourceLocator->expects($this->once())
            ->method('createLocatedSource')
            ->with($identifier)
            ->will($this->returnValue(null));

        self::assertNull($sourceLocator->locateIdentifier($mockReflector, $identifier));
    }

    public function testLocateIdentifierReturnsNullWhenFindLocatorThrowsException() : void
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php class Foo{}', null);

        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        /** @var AstLocator|\PHPUnit_Framework_MockObject_MockObject $astLocator */
        $astLocator = $this->createMock(AstLocator::class);

        $astLocator->expects($this->once())
            ->method('findReflection')
            ->with($mockReflector, $locatedSource, $identifier)
            ->will($this->throwException(new IdentifierNotFound('Foo', $identifier)));

        /** @var AbstractSourceLocator|\PHPUnit_Framework_MockObject_MockObject $sourceLocator */
        $sourceLocator = $this->getMockBuilder(AbstractSourceLocator::class)
            ->setConstructorArgs([$astLocator])
            ->setMethods(['createLocatedSource'])
            ->getMock();

        $sourceLocator->expects($this->once())
            ->method('createLocatedSource')
            ->with($identifier)
            ->will($this->returnValue($locatedSource));

        self::assertNull($sourceLocator->locateIdentifier($mockReflector, $identifier));
    }

    public function testLocateIdentifiersByTypeCallsFindReflectionsOfType() : void
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php class Foo{}', null);

        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        $mockReflection = $this->createMock(ReflectionClass::class);

        /** @var AstLocator|\PHPUnit_Framework_MockObject_MockObject $astLocator */
        $astLocator = $this->createMock(AstLocator::class);

        $astLocator->expects($this->once())
            ->method('findReflectionsOfType')
            ->with($mockReflector, $locatedSource, $identifierType)
            ->will($this->returnValue([$mockReflection]));

        /** @var AbstractSourceLocator|\PHPUnit_Framework_MockObject_MockObject $sourceLocator */
        $sourceLocator = $this->getMockBuilder(AbstractSourceLocator::class)
            ->setConstructorArgs([$astLocator])
            ->setMethods(['createLocatedSource'])
            ->getMock();

        $sourceLocator->expects($this->once())
            ->method('createLocatedSource')
            ->will($this->returnValue($locatedSource));

        self::assertSame([$mockReflection], $sourceLocator->locateIdentifiersByType($mockReflector, $identifierType));
    }

    public function testLocateIdentifiersByTypeReturnsEmptyArrayWithoutTryingToFindReflectionsWhenUnableToLocateSource() : void
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->createMock(Reflector::class);

        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        /** @var AstLocator|\PHPUnit_Framework_MockObject_MockObject $astLocator */
        $astLocator = $this->createMock(AstLocator::class);

        $astLocator->expects($this->never())
            ->method('findReflectionsOfType');

        /** @var AbstractSourceLocator|\PHPUnit_Framework_MockObject_MockObject $sourceLocator */
        $sourceLocator = $this->getMockBuilder(AbstractSourceLocator::class)
            ->setConstructorArgs([$astLocator])
            ->setMethods(['createLocatedSource'])
            ->getMock();

        $sourceLocator->expects($this->once())
            ->method('createLocatedSource')
            ->will($this->returnValue(null));

        self::assertSame([], $sourceLocator->locateIdentifiersByType($mockReflector, $identifierType));
    }
}
