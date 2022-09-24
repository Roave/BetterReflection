<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use ReflectionClassConstant as CoreReflectionClassConstant;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClassConstant as ReflectionClassConstantAdapter;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\Attr;
use Roave\BetterReflectionTest\Fixture\ClassWithAttributes;
use Roave\BetterReflectionTest\Fixture\ClassWithConstants;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use Roave\BetterReflectionTest\Fixture\InterfaceWithConstants;
use Roave\BetterReflectionTest\Fixture\OtherClassWithConstants;
use Roave\BetterReflectionTest\Fixture\ParentClassWithConstants;
use Roave\BetterReflectionTest\Fixture\TraitWithConstants;

use function sprintf;

class ReflectionClassConstantTest extends TestCase
{
    private Locator $astLocator;

    public function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    private function getComposerLocator(): ComposerSourceLocator
    {
        return new ComposerSourceLocator(
            require __DIR__ . '/../../../vendor/autoload.php',
            $this->astLocator,
        );
    }

    /** @param non-empty-string $name */
    private function getExampleConstant(string $name): ReflectionClassConstant|null
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        return $classInfo->getConstant($name);
    }

    public function testDefaultVisibility(): void
    {
        $const = $this->getExampleConstant('MY_CONST_1');
        self::assertTrue($const->isPublic());
    }

    public function testOnlyPublicVisibility(): void
    {
        $const = $this->getExampleConstant('MY_CONST_3');
        self::assertTrue($const->isPublic());
        self::assertFalse($const->isFinal());
    }

    public function testOnlyProtectedVisibility(): void
    {
        $const = $this->getExampleConstant('MY_CONST_4');
        self::assertTrue($const->isProtected());
        self::assertFalse($const->isFinal());
    }

    public function testPrivateVisibility(): void
    {
        $const = $this->getExampleConstant('MY_CONST_5');
        self::assertTrue($const->isPrivate());
        self::assertFalse($const->isFinal());
    }

    public function testPublicFinal(): void
    {
        $const = $this->getExampleConstant('MY_CONST_6');
        self::assertTrue($const->isPublic());
        self::assertTrue($const->isFinal());
    }

    public function testProtectedFinal(): void
    {
        $const = $this->getExampleConstant('MY_CONST_7');
        self::assertTrue($const->isProtected());
        self::assertTrue($const->isFinal());
    }

    public function testToString(): void
    {
        self::assertSame("Constant [ public integer MY_CONST_1 ] { 123 }\n", (string) $this->getExampleConstant('MY_CONST_1'));
    }

    /**
     * @param non-empty-string $const
     *
     * @dataProvider getModifiersProvider
     */
    public function testGetModifiers(string $const, int $expected): void
    {
        self::assertSame($expected, $this->getExampleConstant($const)->getModifiers());
    }

    /** @return list<array{0: non-empty-string, 1: int}> */
    public function getModifiersProvider(): array
    {
        return [
            ['MY_CONST_1', CoreReflectionClassConstant::IS_PUBLIC],
            ['MY_CONST_2', CoreReflectionClassConstant::IS_PUBLIC],
            ['MY_CONST_3', CoreReflectionClassConstant::IS_PUBLIC],
            ['MY_CONST_4', CoreReflectionClassConstant::IS_PROTECTED],
            ['MY_CONST_5', CoreReflectionClassConstant::IS_PRIVATE],
            ['MY_CONST_6', CoreReflectionClassConstant::IS_PUBLIC | ReflectionClassConstantAdapter::IS_FINAL],
            ['MY_CONST_7', CoreReflectionClassConstant::IS_PROTECTED | ReflectionClassConstantAdapter::IS_FINAL],
        ];
    }

    public function getValue(): void
    {
        $const = $this->getExampleConstant('MY_CONST_1');

        self::assertInstanceOf(Node\Expr::class, $const->getValueExpression());
        self::assertSame(123, $const->getValue());
    }

    public function testGetDocComment(): void
    {
        $const = $this->getExampleConstant('MY_CONST_2');
        self::assertStringContainsString('This comment for constant should be used.', $const->getDocComment());
    }

    public function testGetDocCommentReturnsNullWithNoComment(): void
    {
        $const = $this->getExampleConstant('MY_CONST_1');
        self::assertNull($const->getDocComment());
    }

    public function testGetDeclaringClass(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);
        $const     = $classInfo->getConstant('MY_CONST_1');
        self::assertSame($classInfo, $const->getDeclaringClass());
    }

    /**
     * @param non-empty-string $php
     *
     * @dataProvider startEndLineProvider
     */
    public function testStartEndLine(string $php, int $startLine, int $endLine): void
    {
        $reflector       = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflectClass('\T');
        $constReflection = $classReflection->getConstant('TEST');
        self::assertEquals($startLine, $constReflection->getStartLine());
        self::assertEquals($endLine, $constReflection->getEndLine());
    }

    /** @return list<array{0: non-empty-string, 1: int, 2: int}> */
    public function startEndLineProvider(): array
    {
        return [
            ["<?php\nclass T {\nconst TEST = 1; }", 3, 3],
            ["<?php\n\nclass T {\nconst TEST = 1; }", 4, 4],
            ["<?php\nclass T {\nconst TEST = \n1; }", 3, 4],
            ["<?php\nclass T {\nconst \nTEST = 1; }", 3, 4],
        ];
    }

    /** @return list<array{0: non-empty-string, 1: int, 2: int}> */
    public function columnsProvider(): array
    {
        return [
            ["<?php\n\nclass T {\nconst TEST = 1;}", 1, 15],
            ["<?php\n\n    class T {\n        const TEST = 1;}", 9, 23],
            ['<?php class T {const TEST = 1;}', 16, 30],
        ];
    }

    /**
     * @param non-empty-string $php
     *
     * @dataProvider columnsProvider
     */
    public function testGetStartColumnAndEndColumn(string $php, int $startColumn, int $endColumn): void
    {
        $reflector          = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection    = $reflector->reflectClass('T');
        $constantReflection = $classReflection->getConstant('TEST');

        self::assertEquals($startColumn, $constantReflection->getStartColumn());
        self::assertEquals($endColumn, $constantReflection->getEndColumn());
    }

    /** @return list<array{0: non-empty-string, 1: string, 2: string, 3: string}> */
    public function declaringAndImplementingClassesProvider(): array
    {
        return [
            ['CLASS_WINS', ClassWithConstants::class, ClassWithConstants::class, ClassWithConstants::class],
            ['PARENT_WINS', ClassWithConstants::class, ParentClassWithConstants::class, ParentClassWithConstants::class],
            ['TRAIT_WINS', ClassWithConstants::class, TraitWithConstants::class, ClassWithConstants::class],
            ['CLASS_WINS', OtherClassWithConstants::class, OtherClassWithConstants::class, OtherClassWithConstants::class],
            ['INTERFACE_WINS', OtherClassWithConstants::class, InterfaceWithConstants::class, InterfaceWithConstants::class],
            ['TRAIT_WINS', OtherClassWithConstants::class, TraitWithConstants::class, OtherClassWithConstants::class],
        ];
    }

    /**
     * @param non-empty-string $constantName
     *
     * @dataProvider declaringAndImplementingClassesProvider
     */
    public function testGetDeclaringAndImplementingClass(string $constantName, string $currentClassName, string $declaringClassName, string $implementingClassName): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassesWithConstants.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass($currentClassName);
        $constantReflection = $classReflection->getConstant($constantName);

        self::assertSame($declaringClassName, $constantReflection->getDeclaringClass()->getName());
        self::assertSame($implementingClassName, $constantReflection->getImplementingClass()->getName());
    }

    /** @return list<array{0: string, 1: bool}> */
    public function deprecatedDocCommentProvider(): array
    {
        return [
            [
                '/**
                  * @deprecated since 8.0
                  */',
                true,
            ],
            [
                '/**
                  * @deprecated
                  */',
                true,
            ],
            [
                '',
                false,
            ],
        ];
    }

    /** @dataProvider deprecatedDocCommentProvider */
    public function testIsDeprecated(string $docComment, bool $isDeprecated): void
    {
        $php = sprintf('<?php
        class Foo {
            %s
            public const FOO = "foo";
        }', $docComment);

        $reflector          = new DefaultReflector(new StringSourceLocator($php, BetterReflectionSingleton::instance()->astLocator()));
        $classReflection    = $reflector->reflectClass('Foo');
        $constantReflection = $classReflection->getConstant('FOO');

        self::assertSame($isDeprecated, $constantReflection->isDeprecated());
    }

    public function testGetAttributesWithoutAttributes(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ExampleClass::class);
        $constantReflection = $classReflection->getConstant('MY_CONST_1');
        $attributes         = $constantReflection->getAttributes();

        self::assertCount(0, $attributes);
    }

    public function testGetAttributesWithAttributes(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $constantReflection = $classReflection->getConstant('CONSTANT_WITH_ATTRIBUTES');
        $attributes         = $constantReflection->getAttributes();

        self::assertCount(2, $attributes);
    }

    public function testGetAttributesByName(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $constantReflection = $classReflection->getConstant('CONSTANT_WITH_ATTRIBUTES');
        $attributes         = $constantReflection->getAttributesByName(Attr::class);

        self::assertCount(1, $attributes);
    }

    public function testGetAttributesByInstance(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $constantReflection = $classReflection->getConstant('CONSTANT_WITH_ATTRIBUTES');
        $attributes         = $constantReflection->getAttributesByInstance(Attr::class);

        self::assertCount(2, $attributes);
    }

    public function testWithImplementingClass(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $constantReflection = $classReflection->getConstant('CONSTANT_WITH_ATTRIBUTES');
        $attributes         = $constantReflection->getAttributes();

        self::assertCount(2, $attributes);

        $implementingClassReflection = $this->createMock(ReflectionClass::class);

        $cloneConstantReflection = $constantReflection->withImplementingClass($implementingClassReflection);

        self::assertNotSame($constantReflection, $cloneConstantReflection);
        self::assertSame($constantReflection->getDeclaringClass(), $cloneConstantReflection->getDeclaringClass());
        self::assertNotSame($constantReflection->getImplementingClass(), $cloneConstantReflection->getImplementingClass());

        $cloneAttributes = $cloneConstantReflection->getAttributes();

        self::assertCount(2, $cloneAttributes);
        self::assertNotSame($attributes[0], $cloneAttributes[0]);
    }
}
