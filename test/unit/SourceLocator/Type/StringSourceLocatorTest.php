<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Exception\EmptyPhpSourceCode;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\StringSourceLocator
 */
class StringSourceLocatorTest extends TestCase
{
    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    public function testReturnsNullWhenSourceDoesNotContainClass() : void
    {
        $sourceCode = '<?php echo "Hello world!";';

        $locator = new StringSourceLocator($sourceCode);

        self::assertNull($locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier(
                'does not matter what the class name is',
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        ));
    }

    public function testReturnsReflectionWhenSourceHasClass() : void
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

        self::assertSame('Foo', $reflectionClass->getName());
    }

    public function testConstructorThrowsExceptionIfEmptyStringGiven() : void
    {
        $this->expectException(EmptyPhpSourceCode::class);
        new StringSourceLocator('');
    }
}
