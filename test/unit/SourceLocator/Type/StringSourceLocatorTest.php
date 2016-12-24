<?php

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflection\SourceLocator\Exception\EmptyPhpSourceCode;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\StringSourceLocator
 */
class StringSourceLocatorTest extends \PHPUnit_Framework_TestCase
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
        $this->expectException(EmptyPhpSourceCode::class);
        new StringSourceLocator('');
    }
}
