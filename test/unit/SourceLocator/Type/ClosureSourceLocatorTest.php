<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Exception\TwoClosuresOneLine;
use BetterReflection\SourceLocator\Type\ClosureSourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\Type\ClosureSourceLocator
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

    public function testClosureSourceLocator()
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

        $this->assertSame('{closure}', $reflection->getShortName());
        $this->assertContains('Hello world!', $reflection->getLocatedSource()->getSource());
    }

    public function testLocateIdentifiersByTypeIsNotImplemented()
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

    public function testTwoClosuresSameLineFails()
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
