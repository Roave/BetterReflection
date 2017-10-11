<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Type;

use InvalidArgumentException;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Exception\EvaledAnonymousClassCannotBeLocated;
use Rector\BetterReflection\SourceLocator\Exception\TwoAnonymousClassesOnSameLine;
use Rector\BetterReflection\SourceLocator\Type\AnonymousClassObjectSourceLocator;
use Rector\BetterReflection\Util\FileHelper;
use Rector\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Type\AnonymousClassObjectSourceLocator
 */
class AnonymousClassObjectSourceLocatorTest extends TestCase
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

    public function testExceptionThrownWhenNonObjectGiven() : void
    {
        $this->expectException(InvalidArgumentException::class);
        new AnonymousClassObjectSourceLocator(123, $this->parser);
    }

    public function anonymousClassInstancesProvider() : array
    {
        $fileWithClasses                = FileHelper::normalizeWindowsPath(\realpath(__DIR__ . '/../../Fixture/AnonymousClassInstances.php'));
        $fileWithClassWithNestedClasses = FileHelper::normalizeWindowsPath(\realpath(__DIR__ . '/../../Fixture/NestedAnonymousClassInstances.php'));

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

    /**
     * @param object $class
     * @param string $file
     * @param int $startLine
     * @paran int $endLine
     * @dataProvider anonymousClassInstancesProvider
     */
    public function testLocateIdentifier($class, string $file, int $startLine, int $endLine) : void
    {
        /** @var ReflectionClass $reflection */
        $reflection = (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifier(
            $this->reflector,
            new Identifier(
                \get_class($class),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        );

        self::assertTrue($reflection->isAnonymous());
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $reflection->getShortName());
        self::assertSame($file, $reflection->getFileName());
        self::assertSame($startLine, $reflection->getStartLine());
        self::assertSame($endLine, $reflection->getEndLine());
    }

    public function testLocateIdentifierWithFunctionIdentifier() : void
    {
        $anonymousClass = new class {
        };

        /** @var ReflectionClass|null $reflection */
        $reflection = (new AnonymousClassObjectSourceLocator($anonymousClass, $this->parser))->locateIdentifier(
            $this->reflector,
            new Identifier(
                'foo',
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
            )
        );

        self::assertNull($reflection);
    }

    /**
     * @param object $class
     * @param string $file
     * @param int $startLine
     * @paran int $endLine
     * @dataProvider anonymousClassInstancesProvider
     */
    public function testLocateIdentifiersByType($class, string $file, int $startLine, int $endLine) : void
    {
        /** @var ReflectionClass[] $reflections */
        $reflections = (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifiersByType(
            $this->reflector,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        );

        self::assertCount(1, $reflections);
        self::assertArrayHasKey(0, $reflections);

        self::assertTrue($reflections[0]->isAnonymous());
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $reflections[0]->getShortName());
        self::assertSame($file, $reflections[0]->getFileName());
        self::assertSame($startLine, $reflections[0]->getStartLine());
        self::assertSame($endLine, $reflections[0]->getEndLine());
    }

    public function testLocateIdentifiersByTypeWithFunctionIdentifier() : void
    {
        $anonymousClass = new class {
        };

        /** @var ReflectionClass[] $reflections */
        $reflections = (new AnonymousClassObjectSourceLocator($anonymousClass, $this->parser))->locateIdentifiersByType(
            $this->reflector,
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
        );

        self::assertCount(0, $reflections);
    }

    public function exceptionIfTwoAnonymousClassesOnSameLineProvider() : array
    {
        $file    = FileHelper::normalizeWindowsPath(\realpath(__DIR__ . '/../../Fixture/AnonymousClassInstancesOnSameLine.php'));
        $classes = require $file;

        return [
            [$file, $classes[0]],
            [$file, $classes[1]],
        ];
    }

    /**
     * @param string $file
     * @param object $class
     * @dataProvider exceptionIfTwoAnonymousClassesOnSameLineProvider
     */
    public function testExceptionIfTwoAnonymousClassesOnSameLine(string $file, $class) : void
    {
        $this->expectException(TwoAnonymousClassesOnSameLine::class);
        $this->expectExceptionMessage(\sprintf('Two anonymous classes on line 3 in %s', $file));

        (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifier(
            $this->reflector,
            new Identifier(
                \get_class($class),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        );
    }

    public function nestedAnonymousClassInstancesProvider() : array
    {
        $class = require __DIR__ . '/../../Fixture/NestedAnonymousClassInstances.php';

        return [
            [$class, 3, 13],
            [$class->getWrapped(0), 8, 8],
            [$class->getWrapped(1), 11, 11],
        ];
    }

    public function testExceptionIfEvaledAnonymousClass() : void
    {
        $this->expectException(EvaledAnonymousClassCannotBeLocated::class);

        $class = require __DIR__ . '/../../Fixture/EvaledAnonymousClassInstance.php';

        /** @var ReflectionClass $reflection */
        (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifier(
            $this->reflector,
            new Identifier(
                \get_class($class),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        );
    }
}
