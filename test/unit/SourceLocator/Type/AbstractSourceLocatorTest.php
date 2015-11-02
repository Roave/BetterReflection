<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\Exception\IdentifierNotFound;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use BetterReflection\SourceLocator\Located\LocatedSource;
use BetterReflection\SourceLocator\Type\AbstractSourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\Type\AbstractSourceLocator
 */
class AbstractSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testLocateIdentifierCallsFindReflection()
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php class Foo{}', null);

        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $mockReflection = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var AstLocator|\PHPUnit_Framework_MockObject_MockObject $astLocator */
        $astLocator = $this->getMockBuilder(AstLocator::class)
            ->setMethods(['findReflection'])
            ->getMock();

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

        $this->assertSame($mockReflection, $sourceLocator->locateIdentifier($mockReflector, $identifier));
    }

    public function testLocateIdentifierReturnsNullWithoutTryingToFindReflectionWhenUnableToLocateSource()
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        /** @var AstLocator|\PHPUnit_Framework_MockObject_MockObject $astLocator */
        $astLocator = $this->getMockBuilder(AstLocator::class)
            ->setMethods(['findReflection'])
            ->getMock();

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

        $this->assertNull($sourceLocator->locateIdentifier($mockReflector, $identifier));
    }

    public function testLocateIdentifierReturnsNullWhenFindLocatorThrowsException()
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php class Foo{}', null);

        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        /** @var AstLocator|\PHPUnit_Framework_MockObject_MockObject $astLocator */
        $astLocator = $this->getMockBuilder(AstLocator::class)
            ->setMethods(['findReflection'])
            ->getMock();

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

        $this->assertNull($sourceLocator->locateIdentifier($mockReflector, $identifier));
    }

    public function testLocateIdentifiersByTypeCallsFindReflectionsOfType()
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php class Foo{}', null);

        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        $mockReflection = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var AstLocator|\PHPUnit_Framework_MockObject_MockObject $astLocator */
        $astLocator = $this->getMockBuilder(AstLocator::class)
            ->setMethods(['findReflectionsOfType'])
            ->getMock();

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

        $this->assertSame([$mockReflection], $sourceLocator->locateIdentifiersByType($mockReflector, $identifierType));
    }
    public function testLocateIdentifiersByTypeReturnsEmptyArrayWithoutTryingToFindReflectionsWhenUnableToLocateSource()
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php class Foo{}', null);

        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        $mockReflection = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var AstLocator|\PHPUnit_Framework_MockObject_MockObject $astLocator */
        $astLocator = $this->getMockBuilder(AstLocator::class)
            ->setMethods(['findReflectionsOfType'])
            ->getMock();

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

        $this->assertSame([], $sourceLocator->locateIdentifiersByType($mockReflector, $identifierType));
    }
}
