<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Closure;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Exception\EvaledClosureCannotBeLocated;
use Roave\BetterReflection\SourceLocator\Exception\TwoClosuresOnSameLine;
use Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator;
use Roave\BetterReflection\Util\FileHelper;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator
 */
class ClosureSourceLocatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    public function closuresProvider() : array
    {
        $fileWithClosureInNamespace = FileHelper::normalizeWindowsPath(\realpath(__DIR__ . '/../../Fixture/ClosureInNamespace.php'));
        $fileWithClosureNoNamespace = FileHelper::normalizeWindowsPath(\realpath(__DIR__ . '/../../Fixture/ClosureNoNamespace.php'));

        return [
            [require $fileWithClosureInNamespace, 'Roave\BetterReflectionTest\Fixture', $fileWithClosureInNamespace, 5, 8],
            [require $fileWithClosureNoNamespace, '', $fileWithClosureNoNamespace, 3, 6],
        ];
    }

    /**
     * @param Closure $closure
     * @param string $namespace
     * @param string $file
     * @param int $startLine
     * @paran int $endLine
     * @dataProvider closuresProvider
     */
    public function testLocateIdentifier(Closure $closure, string $namespace, string $file, int $startLine, int $endLine) : void
    {
        $locator = new ClosureSourceLocator($closure);

        /** @var ReflectionFunction $reflection */
        $reflection = $locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier(
                'Foo',
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
            )
        );

        self::assertTrue($reflection->isClosure());
        self::assertSame(ReflectionFunction::CLOSURE_NAME, $reflection->getShortName());
        self::assertSame($namespace, $reflection->getNamespaceName());
        self::assertSame($file, $reflection->getFileName());
        self::assertSame($startLine, $reflection->getStartLine());
        self::assertSame($endLine, $reflection->getEndLine());
        self::assertContains('Hello world!', $reflection->getLocatedSource()->getSource());
    }

    public function testEvaledClosureThrowsInvalidFileLocation() : void
    {
        $this->expectException(EvaledClosureCannotBeLocated::class);

        eval('$closure = function () {};');

        $locator = new ClosureSourceLocator($closure);

        /** @var ReflectionFunction $reflection */
        $locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier(
                'Foo',
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
            )
        );
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

        $this->expectException(TwoClosuresOnSameLine::class);

        $locator->locateIdentifier(
            $this->getMockReflector(),
            new Identifier(
                'Foo',
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
            )
        );
    }
}
