<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use LogicException;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\IntEnum;
use Roave\BetterReflectionTest\Fixture\PureEnum;
use Roave\BetterReflectionTest\Fixture\StringEnum;

/** @covers \Roave\BetterReflection\Reflection\ReflectionEnum */
class ReflectionEnumTest extends TestCase
{
    private Locator $astLocator;

    private Reflector $reflector;

    public function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
        $this->reflector  = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator));
    }

    /** @return list<array{0: class-string}> */
    public function dataCanReflect(): array
    {
        return [
            [PureEnum::class],
            [IntEnum::class],
            [StringEnum::class],
        ];
    }

    /** @dataProvider dataCanReflect */
    public function testCanReflect(string $enumName): void
    {
        $enumReflection = $this->reflector->reflectClass($enumName);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);
    }

    /** @return list<array{0: string, 1: bool}> */
    public function dataHasAndGetCase(): array
    {
        return [
            ['ONE', true],
            ['TWO', true],
            ['THREE', true],
            ['FOUR', false],
        ];
    }

    /** @dataProvider dataHasAndGetCase */
    public function testHasAndGetCase(string $caseName, bool $exists): void
    {
        $enumReflection = $this->reflector->reflectClass(PureEnum::class);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        self::assertSame($exists, $enumReflection->hasCase($caseName));

        $case = $enumReflection->getCase($caseName);

        if ($exists) {
            self::assertInstanceOf(ReflectionEnumCase::class, $case);
        } else {
            self::assertNull($case);
        }
    }

    /** @return list<array{0: class-string, 1: int}> */
    public function dataGetCases(): array
    {
        return [
            [PureEnum::class, 3],
            [IntEnum::class, 4],
            [StringEnum::class, 5],
        ];
    }

    /** @dataProvider dataGetCases */
    public function testGetCases(string $enumName, int $casesCount): void
    {
        $enumReflection = $this->reflector->reflectClass($enumName);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $cases = $enumReflection->getCases();

        self::assertCount($casesCount, $cases);
        self::assertContainsOnlyInstancesOf(ReflectionEnumCase::class, $cases);
    }

    /** @return list<array{0: class-string, 1: bool}> */
    public function dataIsBacked(): array
    {
        return [
            [PureEnum::class, false],
            [IntEnum::class, true],
            [StringEnum::class, true],
        ];
    }

    /** @dataProvider dataIsBacked */
    public function testIsBacked(string $enumName, bool $isBacked): void
    {
        $enumReflection = $this->reflector->reflectClass($enumName);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);
        self::assertSame($isBacked, $enumReflection->isBacked());
    }

    /** @return list<array{0: class-string, 1: string}> */
    public function dataGetBackingType(): array
    {
        return [
            [IntEnum::class, 'int'],
            [StringEnum::class, 'string'],
        ];
    }

    /** @dataProvider dataGetBackingType */
    public function testGetBackingType(string $enumName, string $expectedBackingType): void
    {
        $enumReflection = $this->reflector->reflectClass($enumName);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $backingType = $enumReflection->getBackingType();

        self::assertInstanceOf(ReflectionNamedType::class, $backingType);
        self::assertSame($expectedBackingType, $backingType->__toString());
    }

    public function testGetBackingTypeExceptionForPureEnum(): void
    {
        $enumReflection = $this->reflector->reflectClass(PureEnum::class);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        self::expectException(LogicException::class);
        $enumReflection->getBackingType();
    }
}
