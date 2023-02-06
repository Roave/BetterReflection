<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Closure;
use PhpParser\Parser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionFunction as CoreReflectionFunction;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Exception\EvaledClosureCannotBeLocated;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\Exception\NoClosureOnLine;
use Roave\BetterReflection\SourceLocator\Exception\TwoClosuresOnSameLine;
use Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator;
use Roave\BetterReflection\Util\FileHelper;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function assert;
use function is_string;
use function realpath;
use function sprintf;

#[CoversClass(ClosureSourceLocator::class)]
class ClosureSourceLocatorTest extends TestCase
{
    private Parser $parser;

    private Reflector $reflector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser    = BetterReflectionSingleton::instance()->phpParser();
        $this->reflector = $this->createMock(Reflector::class);
    }

    /** @return list<array{0: Closure, 1: string|null, 2: non-empty-string, 3: int, 4: int}> */
    public static function closuresProvider(): array
    {
        $fileWithClosureInNamespace       = FileHelper::normalizeWindowsPath(self::realPath(__DIR__ . '/../../Fixture/ClosureInNamespace.php'));
        $fileWithClosureNoNamespace       = FileHelper::normalizeWindowsPath(self::realPath(__DIR__ . '/../../Fixture/ClosureNoNamespace.php'));
        $fileWithArrowFunctionInNamespace = FileHelper::normalizeWindowsPath(self::realPath(__DIR__ . '/../../Fixture/ArrowFunctionInNamespace.php'));
        $fileWithArrowFunctionNoNamespace = FileHelper::normalizeWindowsPath(self::realPath(__DIR__ . '/../../Fixture/ArrowFunctionNoNamespace.php'));

        return [
            [require $fileWithClosureInNamespace, 'Roave\BetterReflectionTest\Fixture', $fileWithClosureInNamespace, 5, 8],
            [require $fileWithClosureNoNamespace, null, $fileWithClosureNoNamespace, 3, 6],
            [require $fileWithArrowFunctionInNamespace, 'Roave\BetterReflectionTest\Fixture', $fileWithArrowFunctionInNamespace, 5, 5],
            [require $fileWithArrowFunctionNoNamespace, null, $fileWithArrowFunctionNoNamespace, 3, 3],
        ];
    }

    /**
     * @param non-empty-string $file
     *
     * @dataProvider closuresProvider
     */
    public function testLocateIdentifier(Closure $closure, string|null $namespace, string $file, int $startLine, int $endLine): void
    {
        $locator = new ClosureSourceLocator($closure, $this->parser);

        $reflection = $locator->locateIdentifier(
            $this->reflector,
            new Identifier(
                'Foo',
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
            ),
        );

        self::assertInstanceOf(ReflectionFunction::class, $reflection);
        self::assertTrue($reflection->isClosure());
        self::assertSame(ReflectionFunction::CLOSURE_NAME, $reflection->getShortName());
        self::assertSame($namespace, $reflection->getNamespaceName());
        self::assertSame($file, $reflection->getFileName());
        self::assertSame($startLine, $reflection->getStartLine());
        self::assertSame($endLine, $reflection->getEndLine());
        self::assertStringContainsString('Hello world!', $reflection->getLocatedSource()->getSource());
    }

    public function testEvaledClosureThrowsInvalidFileLocation(): void
    {
        eval('$closure = function () {};');

        /** @phpstan-ignore-next-line */
        $locator = new ClosureSourceLocator($closure, $this->parser);

        $this->expectException(EvaledClosureCannotBeLocated::class);

        $locator->locateIdentifier(
            $this->reflector,
            new Identifier(
                'Foo',
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
            ),
        );
    }

    /** @dataProvider closuresProvider */
    public function testLocateIdentifiersByType(Closure $closure, string|null $namespace, string $file, int $startLine, int $endLine): void
    {
        /** @var list<ReflectionFunction> $reflections */
        $reflections = (new ClosureSourceLocator($closure, $this->parser))->locateIdentifiersByType(
            $this->reflector,
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
        );

        self::assertCount(1, $reflections);
        self::assertArrayHasKey(0, $reflections);

        self::assertTrue($reflections[0]->isClosure());
        self::assertSame(ReflectionFunction::CLOSURE_NAME, $reflections[0]->getShortName());
        self::assertSame($namespace, $reflections[0]->getNamespaceName());
        self::assertSame($file, $reflections[0]->getFileName());
        self::assertSame($startLine, $reflections[0]->getStartLine());
        self::assertSame($endLine, $reflections[0]->getEndLine());
        self::assertStringContainsString('Hello world!', $reflections[0]->getLocatedSource()->getSource());
    }

    public function testExceptionIfClosureNotFoundOnExpectedLine(): void
    {
        $closure = static function (): void {
        };

        $sourceLocator = new ClosureSourceLocator($closure, $this->parser);

        $sourceLocatorReflection = new CoreReflectionClass($sourceLocator);

        $coreReflectionPropertyMock = $this->createMock(CoreReflectionFunction::class);
        $coreReflectionPropertyMock
            ->method('getFileName')
            ->willReturn(__FILE__);
        $coreReflectionPropertyMock
            ->method('getStartLine')
            ->willReturn(0);

        $coreReflectionPropertyInSourceLocatatorReflection = $sourceLocatorReflection->getProperty('coreFunctionReflection');
        $coreReflectionPropertyInSourceLocatatorReflection->setAccessible(true);
        $coreReflectionPropertyInSourceLocatatorReflection->setValue($sourceLocator, $coreReflectionPropertyMock);

        $this->expectException(NoClosureOnLine::class);

        $sourceLocator->locateIdentifiersByType($this->reflector, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));
    }

    public function testLocateIdentifiersByTypeWithClassIdentifier(): void
    {
        $closure = static function (): void {
        };

        /** @var list<ReflectionFunction> $reflections */
        $reflections = (new ClosureSourceLocator($closure, $this->parser))->locateIdentifiersByType(
            $this->reflector,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
        );

        self::assertCount(0, $reflections);
    }

    /** @return list<array{0: string, 1: Closure}> */
    public static function exceptionIfTwoClosuresOnSameLineProvider(): array
    {
        $file     = FileHelper::normalizeWindowsPath(self::realPath(__DIR__ . '/../../Fixture/ClosuresOnSameLine.php'));
        $closures = require $file;

        return [
            [$file, $closures[0]],
            [$file, $closures[1]],
        ];
    }

    /** @dataProvider exceptionIfTwoClosuresOnSameLineProvider */
    public function testTwoClosuresSameLineFails(string $file, Closure $closure): void
    {
        $this->expectException(TwoClosuresOnSameLine::class);
        $this->expectExceptionMessage(sprintf('Two closures on line 3 in %s', $file));

        (new ClosureSourceLocator($closure, $this->parser))->locateIdentifier(
            $this->reflector,
            new Identifier(
                'Foo',
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
            ),
        );
    }

    public function testNamesAreResolved(): void
    {
        $closure = require __DIR__ . '/../../Fixture/ClosureWithParameterWithClassFromNamespace.php';

        $sourceLocator = new ClosureSourceLocator($closure, $this->parser);
        $reflector     = new DefaultReflector(BetterReflectionSingleton::instance()->sourceLocator());

        $reflection = $sourceLocator->locateIdentifier(
            $reflector,
            new Identifier(
                ReflectionFunction::CLOSURE_NAME,
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
            ),
        );

        self::assertInstanceOf(ReflectionFunction::class, $reflection);
        self::assertSame('Roave\BetterReflectionTest\Fixture\ClassUsedAsClosureParameter', $reflection->getParameter('parameter')->getType()?->__toString());
    }

    public function testExceptionIfSourceFileIsNotReadable(): void
    {
        $sourceLocator = new ClosureSourceLocator(static function (): void {
        }, $this->parser);

        $sourceLocatorReflectionCoreFunctionReflectionPropertyValue = $this->createMock(CoreReflectionFunction::class);
        $sourceLocatorReflectionCoreFunctionReflectionPropertyValue
            ->method('getFileName')
            ->willReturn('sdklfjdfslsdfhlkjsdglkjsdflgkj');

        $sourceLocatorReflection                               = new CoreReflectionClass($sourceLocator);
        $sourceLocatorReflectionCoreFunctionReflectionProperty = $sourceLocatorReflection->getProperty('coreFunctionReflection');
        $sourceLocatorReflectionCoreFunctionReflectionProperty->setAccessible(true);
        $sourceLocatorReflectionCoreFunctionReflectionProperty->setValue($sourceLocator, $sourceLocatorReflectionCoreFunctionReflectionPropertyValue);

        $this->expectException(InvalidFileLocation::class);
        $sourceLocator->locateIdentifier($this->reflector, new Identifier('whatever', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)));
    }

    /** @return non-empty-string */
    private static function realPath(string|false $path): string
    {
        $realPath = realpath($path);

        assert(is_string($realPath) && $realPath !== '');

        return $realPath;
    }
}
