<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Exception\EvaledAnonymousClassCannotBeLocated;
use Roave\BetterReflection\SourceLocator\Exception\TwoAnonymousClassesOnSameLine;
use Roave\BetterReflection\SourceLocator\Type\AnonymousClassObjectSourceLocator;
use Roave\BetterReflection\Util\FileHelper;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\AnonymousClassObjectSourceLocator
 */
class AnonymousClassObjectSourceLocatorTest extends \PHPUnit\Framework\TestCase
{
    public function testExceptionThrownWhenNonObjectGiven() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AnonymousClassObjectSourceLocator(123);
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
        $reflection = (new AnonymousClassObjectSourceLocator($class))->locateIdentifier(
            $this->createMock(Reflector::class),
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
        $reflection = (new AnonymousClassObjectSourceLocator($anonymousClass))->locateIdentifier(
            $this->createMock(Reflector::class),
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
        $reflections = (new AnonymousClassObjectSourceLocator($class))->locateIdentifiersByType(
            $this->createMock(Reflector::class),
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
        $reflections = (new AnonymousClassObjectSourceLocator($anonymousClass))->locateIdentifiersByType(
            $this->createMock(Reflector::class),
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
        self::expectException(TwoAnonymousClassesOnSameLine::class);
        self::expectExceptionMessage(\sprintf('Two anonymous classes on line 3 in %s', $file));

        (new AnonymousClassObjectSourceLocator($class))->locateIdentifier(
            $this->createMock(Reflector::class),
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
        self::expectException(EvaledAnonymousClassCannotBeLocated::class);

        $class = require __DIR__ . '/../../Fixture/EvaledAnonymousClassInstance.php';

        /** @var ReflectionClass $reflection */
        (new AnonymousClassObjectSourceLocator($class))->locateIdentifier(
            $this->createMock(Reflector::class),
            new Identifier(
                \get_class($class),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        );
    }
}
