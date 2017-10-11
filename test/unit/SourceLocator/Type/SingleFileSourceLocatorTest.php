<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator
 */
class SingleFileSourceLocatorTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

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

        $locator = new SingleFileSourceLocator($fileName, $this->astLocator);

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

        $locator = new SingleFileSourceLocator($fileName, $this->astLocator);

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
