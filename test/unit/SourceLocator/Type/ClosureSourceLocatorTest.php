<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Exception\TwoClosuresOneLine;
use Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator
 */
class ClosureSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    public function testClosureSourceLocator() : void
    {
        $closure = function () {
            echo 'Hello world!';
        };

        $locator = new ClosureSourceLocator($closure);

        /** @var ReflectionFunction $reflection */
        $reflection = $locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier(
                'Foo',
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
            )
        );

        self::assertSame('{closure}', $reflection->getShortName());
        self::assertContains('Hello world!', $reflection->getLocatedSource()->getSource());
    }

    public function testLocateIdentifiersByTypeIsNotImplemented() : void
    {
        $closure = function () {
            echo 'Hello world!';
        };

        $locator = new ClosureSourceLocator($closure);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Not implemented');
        $locator->locateIdentifiersByType(
            $this->getMockReflector(),
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
        );
    }

    public function testTwoClosuresSameLineFails() : void
    {
        $closure1 = function () {}; $closure2 = function () {};

        $locator = new ClosureSourceLocator($closure1);

        $this->expectException(TwoClosuresOneLine::class);

        $locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier(
                'Foo',
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
            )
        );
    }
}
