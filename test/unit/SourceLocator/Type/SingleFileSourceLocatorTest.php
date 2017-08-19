<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator
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

    public function testReturnsNullWhenSourceDoesNotContainClass() : void
    {
        $fileName = __DIR__ . '/../../Fixture/NoNamespace.php';

        $locator = new SingleFileSourceLocator($fileName);

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
        $fileName = __DIR__ . '/../../Fixture/NoNamespace.php';

        $locator = new SingleFileSourceLocator($fileName);

        $reflectionClass = $locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier(
                'ClassWithNoNamespace',
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        );

        self::assertSame('ClassWithNoNamespace', $reflectionClass->getName());
    }
}
