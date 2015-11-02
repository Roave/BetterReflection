<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Type\StringSourceLocator;
use BetterReflection\SourceLocator\Exception\EmptyPhpSourceCode;

/**
 * @covers \BetterReflection\SourceLocator\Type\StringSourceLocator
 */
class StringSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->getMock(Reflector::class);
    }

    public function testReturnsNullWhenSourceDoesNotContainClass()
    {
        $sourceCode = '<?php echo "Hello world!";';

        $locator = new StringSourceLocator($sourceCode);

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
        $sourceCode = '<?php class Foo {}';

        $locator = new StringSourceLocator($sourceCode);

        $reflectionClass = $locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier(
                'Foo',
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        );

        $this->assertSame('Foo', $reflectionClass->getName());
    }

    public function testConstructorThrowsExceptionIfEmptyStringGiven()
    {
        $this->setExpectedException(EmptyPhpSourceCode::class);
        new StringSourceLocator('');
    }
}
