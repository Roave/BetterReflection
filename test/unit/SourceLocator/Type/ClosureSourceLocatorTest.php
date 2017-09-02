<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Closure;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Exception\EvaledClosureCannotBeLocated;
use Roave\BetterReflection\SourceLocator\Exception\TwoClosuresOnSameLine;
use Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator;
use Roave\BetterReflection\Util\FileHelper;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator
 */
class ClosureSourceLocatorTest extends TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Reflector
     */
    private $reflector;

    protected function setUp() : void
    {
        parent::setUp();

        $this->parser    = BetterReflectionSingleton::instance()->phpParser();
        $this->reflector = $this->createMock(Reflector::class);
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
        $locator = new ClosureSourceLocator($closure, $this->parser);

        /** @var ReflectionFunction $reflection */
        $reflection = $locator->locateIdentifier(
            $this->reflector,
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

        $locator = new ClosureSourceLocator($closure, $this->parser);

        /** @var ReflectionFunction $reflection */
        $locator->locateIdentifier(
            $this->reflector,
            new Identifier(
                'Foo',
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
            )
        );
    }

    /**
     * @param Closure $closure
     * @param string $namespace
     * @param string $file
     * @param int $startLine
     * @paran int $endLine
     * @dataProvider closuresProvider
     */
    public function testLocateIdentifiersByType(Closure $closure, string $namespace, string $file, int $startLine, int $endLine) : void
    {
        /** @var ReflectionFunction[] $reflections */
        $reflections = (new ClosureSourceLocator($closure, $this->parser))->locateIdentifiersByType(
            $this->reflector,
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
        );

        self::assertCount(1, $reflections);
        self::assertArrayHasKey(0, $reflections);

        self::assertTrue($reflections[0]->isClosure());
        self::assertSame(ReflectionFunction::CLOSURE_NAME, $reflections[0]->getShortName());
        self::assertSame($namespace, $reflections[0]->getNamespaceName());
        self::assertSame($file, $reflections[0]->getFileName());
        self::assertSame($startLine, $reflections[0]->getStartLine());
        self::assertSame($endLine, $reflections[0]->getEndLine());
        self::assertContains('Hello world!', $reflections[0]->getLocatedSource()->getSource());
    }

    public function testLocateIdentifiersByTypeWithClassIdentifier() : void
    {
        $closure = function () : void {
        };

        /** @var ReflectionFunction[] $reflections */
        $reflections = (new ClosureSourceLocator($closure, $this->parser))->locateIdentifiersByType(
            $this->reflector,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        );

        self::assertCount(0, $reflections);
    }

    public function exceptionIfTwoClosuresOnSameLineProvider() : array
    {
        $file     = FileHelper::normalizeWindowsPath(\realpath(__DIR__ . '/../../Fixture/ClosuresOnSameLine.php'));
        $closures = require $file;

        return [
            [$file, $closures[0]],
            [$file, $closures[1]],
        ];
    }

    /**
     * @param string $file
     * @param Closure $closure
     * @dataProvider exceptionIfTwoClosuresOnSameLineProvider
     */
    public function testTwoClosuresSameLineFails(string $file, Closure $closure) : void
    {
        $this->expectException(TwoClosuresOnSameLine::class);
        $this->expectExceptionMessage(\sprintf('Two closures on line 3 in %s', $file));

        (new ClosureSourceLocator($closure, $this->parser))->locateIdentifier(
            $this->reflector,
            new Identifier(
                'Foo',
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
            )
        );
    }
}
