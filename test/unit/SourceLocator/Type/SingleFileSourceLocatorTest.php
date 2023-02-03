<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/** @covers \Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator */
class SingleFileSourceLocatorTest extends TestCase
{
    private Locator $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    private function getMockReflector(): Reflector|MockObject
    {
        return $this->createMock(Reflector::class);
    }

    public function testReturnsNullWhenSourceDoesNotContainClass(): void
    {
        $fileName = __DIR__ . '/../../Fixture/NoNamespace.php';

        $locator = new SingleFileSourceLocator($fileName, $this->astLocator);

        self::assertNull($locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier(
                'does not matter what the class name is',
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
            ),
        ));
    }

    public function testReturnsReflectionWhenSourceHasClass(): void
    {
        $fileName = __DIR__ . '/../../Fixture/NoNamespace.php';

        $locator = new SingleFileSourceLocator($fileName, $this->astLocator);

        $reflectionClass = $locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier(
                'ClassWithNoNamespace',
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
            ),
        );

        self::assertSame('ClassWithNoNamespace', $reflectionClass->getName());
    }

    public function testThrowsExceptionIfFileIsNotReadable(): void
    {
        $this->expectException(InvalidFileLocation::class);
        new SingleFileSourceLocator('not-readable', $this->astLocator);
    }
}
