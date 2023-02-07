<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use LogicException;
use PhpParser\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionEnumCase;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\Attr;
use Roave\BetterReflectionTest\Fixture\DocComment;
use Roave\BetterReflectionTest\Fixture\IntEnum;
use Roave\BetterReflectionTest\Fixture\IsDeprecated;
use Roave\BetterReflectionTest\Fixture\PureEnum;
use Roave\BetterReflectionTest\Fixture\StringEnum;

#[CoversClass(ReflectionEnumCase::class)]
class ReflectionEnumCaseTest extends TestCase
{
    private Locator $astLocator;

    private Reflector $reflector;

    public function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
        $this->reflector  = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator));
    }

    /** @return list<array{0: class-string, 1: non-empty-string}> */
    public static function data(): array
    {
        return [
            [PureEnum::class, 'ONE'],
            [IntEnum::class, 'TWO'],
            [StringEnum::class, 'THREE'],
        ];
    }

    /** @param non-empty-string $caseName */
    #[DataProvider('data')]
    public function testCanReflect(string $enumName, string $caseName): void
    {
        $enumReflection = $this->reflector->reflectClass($enumName);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $caseReflection = $enumReflection->getCase($caseName);

        self::assertInstanceOf(ReflectionEnumCase::class, $caseReflection);
        self::assertSame($caseName, $caseReflection->getName());
    }

    /** @return list<array{0: class-string, 1: non-empty-string, 2: int|string}> */
    public static function dataGetValue(): array
    {
        return [
            [IntEnum::class, 'TWO', 2],
            [StringEnum::class, 'THREE', 'three'],
        ];
    }

    /** @param non-empty-string $caseName */
    #[DataProvider('dataGetValue')]
    public function testGetValue(string $enumName, string $caseName, int|string $value): void
    {
        $enumReflection = $this->reflector->reflectClass($enumName);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $caseReflection = $enumReflection->getCase($caseName);

        self::assertInstanceOf(ReflectionEnumCase::class, $caseReflection);
        self::assertInstanceOf(Node\Expr::class, $caseReflection->getValueExpression());
        self::assertSame($value, $caseReflection->getValue());
    }

    public function testGetValueExpressionThrowsExceptionForPureEnum(): void
    {
        $enumReflection = $this->reflector->reflectClass(PureEnum::class);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $caseReflection = $enumReflection->getCase('ONE');

        $this->expectException(LogicException::class);
        $caseReflection->getValueExpression();
    }

    public function testGetValueThrowsExceptionForPureEnum(): void
    {
        $enumReflection = $this->reflector->reflectClass(PureEnum::class);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $caseReflection = $enumReflection->getCase('ONE');

        $this->expectException(LogicException::class);
        $caseReflection->getValue();
    }

    /** @return list<array{0: class-string, 1: string, 2: int, 3: int, 4: int, 5: int}> */
    public static function dataLinesAndColums(): array
    {
        return [
            [PureEnum::class, 'ONE', 7, 7, 5, 13],
            [IntEnum::class, 'TWO', 19, 19, 5, 17],
            [StringEnum::class, 'THREE', 34, 35, 5, 18],
        ];
    }

    /** @param non-empty-string $caseName */
    #[DataProvider('dataLinesAndColums')]
    public function testLinesAndColums(string $enumName, string $caseName, int $startLine, int $endLine, int $startColumn, int $endColumn): void
    {
        $enumReflection = $this->reflector->reflectClass($enumName);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $caseReflection = $enumReflection->getCase($caseName);

        self::assertSame($startLine, $caseReflection->getStartLine());
        self::assertSame($endLine, $caseReflection->getEndLine());
        self::assertSame($startColumn, $caseReflection->getStartColumn());
        self::assertSame($endColumn, $caseReflection->getEndColumn());
    }

    /** @param non-empty-string $caseName */
    #[DataProvider('data')]
    public function testGetDeclaringClassAndEnum(string $enumName, string $caseName): void
    {
        $enumReflection = $this->reflector->reflectClass($enumName);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $caseReflection = $enumReflection->getCase($caseName);

        self::assertSame($enumReflection, $caseReflection->getDeclaringClass());
        self::assertSame($enumReflection, $caseReflection->getDeclaringEnum());
    }

    /** @return list<array{0: non-empty-string, 1: string|null}> */
    public static function dataGetDocComment(): array
    {
        return [
            ['WITH_DOCCOMMENT', '/** With doccomment */'],
            ['NO_DOCCOMMENT', null],
        ];
    }

    /** @param non-empty-string $caseName */
    #[DataProvider('dataGetDocComment')]
    public function testGetDocComment(string $caseName, string|null $docComment): void
    {
        $enumReflection = $this->reflector->reflectClass(DocComment::class);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $caseReflection = $enumReflection->getCase($caseName);

        self::assertSame($docComment, $caseReflection->getDocComment());
    }

    /** @return list<array{0: non-empty-string, 1: bool}> */
    public static function dataIsDeprecated(): array
    {
        return [
            ['IS_DEPRECATED', true],
            ['IS_NOT_DEPRECATED', false],
        ];
    }

    /** @param non-empty-string $caseName */
    #[DataProvider('dataIsDeprecated')]
    public function testIsDeprecated(string $caseName, bool $isDeprecated): void
    {
        $enumReflection = $this->reflector->reflectClass(IsDeprecated::class);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $caseReflection = $enumReflection->getCase($caseName);

        self::assertSame($isDeprecated, $caseReflection->isDeprecated());
    }

    public function testGetAttributesWithoutAttributes(): void
    {
        $enumReflection = $this->reflector->reflectClass(PureEnum::class);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $caseReflection = $enumReflection->getCase('ONE');
        $attributes     = $caseReflection->getAttributes();

        self::assertCount(0, $attributes);
    }

    public function testGetAttributesWithAttributes(): void
    {
        $reflector      = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $enumReflection = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\EnumWithAttributes');

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $caseReflection = $enumReflection->getCase('CASE_WITH_ATTRIBUTES');
        $attributes     = $caseReflection->getAttributes();

        self::assertCount(2, $attributes);
    }

    public function testGetAttributesByName(): void
    {
        $reflector      = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $enumReflection = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\EnumWithAttributes');

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $caseReflection = $enumReflection->getCase('CASE_WITH_ATTRIBUTES');
        $attributes     = $caseReflection->getAttributesByName(Attr::class);

        self::assertCount(1, $attributes);
    }

    public function testGetAttributesByInstance(): void
    {
        $reflector      = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $enumReflection = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\EnumWithAttributes');

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $caseReflection = $enumReflection->getCase('CASE_WITH_ATTRIBUTES');
        $attributes     = $caseReflection->getAttributesByInstance(Attr::class);

        self::assertCount(2, $attributes);
    }

    public function testToString(): void
    {
        $enumReflection = $this->reflector->reflectClass(PureEnum::class);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        self::assertSame("Constant [ public Roave\BetterReflectionTest\Fixture\PureEnum ONE ] { Object }\n", (string) $enumReflection->getCase('ONE'));
    }
}
