<?php

namespace BetterReflectionTest\SourceLocator;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use BetterReflection\SourceLocator\SingleFileSourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\SingleFileSourceLocator
 */
class SingleFileSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testInvokableLoadsSource()
    {
        $fileName = __DIR__ . '/../Fixture/NoNamespace.php';
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
        $this->setExpectedException(InvalidFileLocation::class);
        new SingleFileSourceLocator('');
    }
}
