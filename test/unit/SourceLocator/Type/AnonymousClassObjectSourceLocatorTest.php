<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Exception\EvaledAnonymousClassCannotBeLocated;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\Exception\NoAnonymousClassOnLine;
use Roave\BetterReflection\SourceLocator\Exception\TwoAnonymousClassesOnSameLine;
use Roave\BetterReflection\SourceLocator\Type\AnonymousClassObjectSourceLocator;
use Roave\BetterReflection\Util\FileHelper;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use stdClass;

use function assert;
use function is_string;
use function realpath;
use function sprintf;

/** @covers \Roave\BetterReflection\SourceLocator\Type\AnonymousClassObjectSourceLocator */
class AnonymousClassObjectSourceLocatorTest extends TestCase
{
    private Parser $parser;

    private Reflector $reflector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser    = BetterReflectionSingleton::instance()->phpParser();
        $this->reflector = $this->createMock(Reflector::class);
    }

    /** @return list<array{0: object, 1: string, 2: int, 3: int}> */
    public function anonymousClassInstancesProvider(): array
    {
        $fileWithClasses                = FileHelper::normalizeWindowsPath(self::realPath(__DIR__ . '/../../Fixture/AnonymousClassInstances.php'));
        $fileWithClassWithNestedClasses = FileHelper::normalizeWindowsPath(self::realPath(__DIR__ . '/../../Fixture/NestedAnonymousClassInstances.php'));

        $classes                = require $fileWithClasses;
        $classWithNestedClasses = require $fileWithClassWithNestedClasses;

        return [
            [$classes[0], $fileWithClasses, 3, 9],
            [$classes[1], $fileWithClasses, 11, 17],
            [$classWithNestedClasses, $fileWithClassWithNestedClasses, 3, 13],
            [$classWithNestedClasses->getWrapped(0), $fileWithClassWithNestedClasses, 8, 8],
            [$classWithNestedClasses->getWrapped(1), $fileWithClassWithNestedClasses, 11, 11],
        ];
    }

    /** @dataProvider anonymousClassInstancesProvider */
    public function testLocateIdentifier(object $class, string $file, int $startLine, int $endLine): void
    {
        $reflection = (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifier(
            $this->reflector,
            new Identifier(
                $class::class,
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
            ),
        );

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertTrue($reflection->isAnonymous());
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $reflection->getShortName());
        self::assertSame($file, $reflection->getFileName());
        self::assertSame($startLine, $reflection->getStartLine());
        self::assertSame($endLine, $reflection->getEndLine());
    }

    public function testCannotLocateNonAnonymousClass(): void
    {
        $class = new CoreReflectionClass(stdClass::class);

        $reflection = (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifier(
            $this->reflector,
            new Identifier(
                $class::class,
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
            ),
        );

        self::assertNull($reflection);
    }

    public function testLocateIdentifierWithFunctionIdentifier(): void
    {
        $anonymousClass = new class {
        };

        $reflection = (new AnonymousClassObjectSourceLocator($anonymousClass, $this->parser))->locateIdentifier(
            $this->reflector,
            new Identifier(
                'foo',
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
            ),
        );

        self::assertNull($reflection);
    }

    /** @dataProvider anonymousClassInstancesProvider */
    public function testLocateIdentifiersByType(object $class, string $file, int $startLine, int $endLine): void
    {
        /** @var list<ReflectionClass> $reflections */
        $reflections = (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifiersByType(
            $this->reflector,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
        );

        self::assertCount(1, $reflections);
        self::assertArrayHasKey(0, $reflections);

        self::assertTrue($reflections[0]->isAnonymous());
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $reflections[0]->getShortName());
        self::assertSame($file, $reflections[0]->getFileName());
        self::assertSame($startLine, $reflections[0]->getStartLine());
        self::assertSame($endLine, $reflections[0]->getEndLine());
    }

    public function testLocateIdentifiersByTypeWithFunctionIdentifier(): void
    {
        $anonymousClass = new class {
        };

        /** @var list<ReflectionClass> $reflections */
        $reflections = (new AnonymousClassObjectSourceLocator($anonymousClass, $this->parser))->locateIdentifiersByType(
            $this->reflector,
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
        );

        self::assertCount(0, $reflections);
    }

    public function testExceptionIfAnonymousClassNotFoundOnExpectedLine(): void
    {
        $anonymousClass = new class {
        };

        $sourceLocator = new AnonymousClassObjectSourceLocator($anonymousClass, $this->parser);

        $sourceLocatorReflection = new CoreReflectionClass($sourceLocator);

        $coreReflectionPropertyMock = $this->createMock(CoreReflectionClass::class);
        $coreReflectionPropertyMock
            ->method('isAnonymous')
            ->willReturn(true);
        $coreReflectionPropertyMock
            ->method('getFileName')
            ->willReturn(__FILE__);
        $coreReflectionPropertyMock
            ->method('getStartLine')
            ->willReturn(0);

        $coreReflectionPropertyInSourceLocatatorReflection = $sourceLocatorReflection->getProperty('coreClassReflection');
        $coreReflectionPropertyInSourceLocatatorReflection->setAccessible(true);
        $coreReflectionPropertyInSourceLocatatorReflection->setValue($sourceLocator, $coreReflectionPropertyMock);

        self::expectException(NoAnonymousClassOnLine::class);

        $sourceLocator->locateIdentifiersByType($this->reflector, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
    }

    /** @return list<array{0: string, 1: object}> */
    public function exceptionIfTwoAnonymousClassesOnSameLineProvider(): array
    {
        $file    = FileHelper::normalizeWindowsPath(self::realPath(__DIR__ . '/../../Fixture/AnonymousClassInstancesOnSameLine.php'));
        $classes = require $file;

        return [
            [$file, $classes[0]],
            [$file, $classes[1]],
        ];
    }

    /** @dataProvider exceptionIfTwoAnonymousClassesOnSameLineProvider */
    public function testExceptionIfTwoAnonymousClassesOnSameLine(string $file, object $class): void
    {
        $this->expectException(TwoAnonymousClassesOnSameLine::class);
        $this->expectExceptionMessage(sprintf('Two anonymous classes on line 3 in %s', $file));

        (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifier(
            $this->reflector,
            new Identifier(
                $class::class,
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
            ),
        );
    }

    /** @return list<array{0: object, 1: int, 2: int}> */
    public function nestedAnonymousClassInstancesProvider(): array
    {
        $class = require __DIR__ . '/../../Fixture/NestedAnonymousClassInstances.php';

        return [
            [$class, 3, 13],
            [$class->getWrapped(0), 8, 8],
            [$class->getWrapped(1), 11, 11],
        ];
    }

    public function testExceptionIfEvaledAnonymousClass(): void
    {
        $class = require __DIR__ . '/../../Fixture/EvaledAnonymousClassInstance.php';

        $this->expectException(EvaledAnonymousClassCannotBeLocated::class);

        (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifier(
            $this->reflector,
            new Identifier(
                $class::class,
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
            ),
        );
    }

    public function testNamesAreResolved(): void
    {
        $class = require __DIR__ . '/../../Fixture/AnonymousClassExtendingClassFromNamespace.php';

        $sourceLocator = new AnonymousClassObjectSourceLocator($class, $this->parser);
        $reflector     = new DefaultReflector(BetterReflectionSingleton::instance()->sourceLocator());

        $reflection = $sourceLocator->locateIdentifier(
            $reflector,
            new Identifier(
                $class::class,
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
            ),
        );

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('Roave\BetterReflectionTest\Fixture\AnonymousClassParent', $reflection->getParentClass()->getName());
    }

    public function testExceptionIfSourceFileIsNotReadable(): void
    {
        $class = $this->createMock(stdClass::class);

        $sourceLocator = new AnonymousClassObjectSourceLocator($class, $this->parser);

        $sourceLocatorReflectionCoreClassReflectionPropertyValue = $this->createMock(CoreReflectionClass::class);
        $sourceLocatorReflectionCoreClassReflectionPropertyValue
            ->method('isAnonymous')
            ->willReturn(true);
        $sourceLocatorReflectionCoreClassReflectionPropertyValue
            ->method('getFileName')
            ->willReturn('sdklfjdfslsdfhlkjsdglkjsdflgkj');

        $sourceLocatorReflection                            = new CoreReflectionClass($sourceLocator);
        $sourceLocatorReflectionCoreClassReflectionProperty = $sourceLocatorReflection->getProperty('coreClassReflection');
        $sourceLocatorReflectionCoreClassReflectionProperty->setAccessible(true);
        $sourceLocatorReflectionCoreClassReflectionProperty->setValue($sourceLocator, $sourceLocatorReflectionCoreClassReflectionPropertyValue);

        $this->expectException(InvalidFileLocation::class);
        $sourceLocator->locateIdentifier($this->reflector, new Identifier(stdClass::class, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));
    }

    /** @return non-empty-string */
    private static function realPath(string|false $path): string
    {
        $realPath = realpath($path);

        assert(is_string($realPath) && $realPath !== '');

        return $realPath;
    }
}
