<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\Type\SingleFileSourceLocator
 */
class SingleFileSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testInvokableLoadsSource()
    {
        $fileName = __DIR__ . '/../../Fixture/NoNamespace.php';
        $expectedContent = file_get_contents($fileName);

        $locator = new SingleFileSourceLocator($fileName);

        $locatedSource = $locator->__invoke(new Identifier(
            'does not matter what the class name is',
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        ));

        $this->assertSame($expectedContent, $locatedSource->getSource());
        $this->assertSame($fileName, $locatedSource->getFileName());
    }

    public function testConstructorThrowsExceptionIfEmptyFileGiven()
    {
        $this->setExpectedException(InvalidFileLocation::class, 'Filename was empty');
        new SingleFileSourceLocator('');
    }

    public function testConstructorThrowsExceptionIfFileDoesNotExist()
    {
        $this->setExpectedException(InvalidFileLocation::class, 'File does not exist');
        new SingleFileSourceLocator('sdklfjdfslsdfhlkjsdglkjsdflgkj');
    }

    public function testConstructorThrowsExceptionIfFileIsNotAFile()
    {
        $this->setExpectedException(InvalidFileLocation::class, 'Is not a file');
        new SingleFileSourceLocator(__DIR__);
    }
}
