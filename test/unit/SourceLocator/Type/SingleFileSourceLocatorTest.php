<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\Type\SingleFileSourceLocator
 */
class SingleFileSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    public function testReturnsNullWhenSourceDoesNotContainClass()
    {
        $fileName = __DIR__ . '/../../Fixture/NoNamespace.php';

        $locator = new SingleFileSourceLocator($fileName);

        $this->assertNull($locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier(
                'does not matter what the class name is',
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        ));
    }

    public function testReturnsReflectionWhenSourceHasClass()
    {
        $fileName = __DIR__ . '/../../Fixture/NoNamespace.php';

        $locator = new SingleFileSourceLocator($fileName);

        $reflectionClass = $locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier(
                'ClassWithNoNamespace',
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        );

        $this->assertSame('ClassWithNoNamespace', $reflectionClass->getName());
    }

    public function testConstructorThrowsExceptionIfEmptyFileGiven()
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('Filename was empty');
        new SingleFileSourceLocator('');
    }

    public function testConstructorThrowsExceptionIfFileDoesNotExist()
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('File does not exist');
        new SingleFileSourceLocator('sdklfjdfslsdfhlkjsdglkjsdflgkj');
    }

    public function testConstructorThrowsExceptionIfFileIsNotAFile()
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('Is not a file');
        new SingleFileSourceLocator(__DIR__);
    }
}
