<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use PhpParser\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType;
use Roave\BetterReflection\Reflector\Reflector;

#[CoversClass(ReflectionType::class)]
#[CoversClass(ReflectionNamedType::class)]
#[CoversClass(ReflectionIntersectionType::class)]
#[CoversClass(ReflectionUnionType::class)]
class ReflectionTypeTest extends TestCase
{
    private Reflector $reflector;
    private ReflectionParameter $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflector = $this->createMock(Reflector::class);
        $this->owner     = $this->createMock(ReflectionParameter::class);
    }

    /** @return array<int|string, array{0: Node\Identifier|Node\Name|Node\NullableType|Node\UnionType|Node\IntersectionType, 1: bool, 2: string, 3: string, 4: bool}> */
    public static function dataProvider(): array
    {
        return [
            [new Node\Name('A'), false, ReflectionNamedType::class, 'A', false],
            [new Node\Identifier('string'), false, ReflectionNamedType::class, 'string', false],
            'Forcing a type to be nullable turns it into a `T|null` ReflectionUnionType' => [
                new Node\Identifier('string'),
                true,
                ReflectionUnionType::class,
                'string|null',
                true,
            ],
            'Nullable types are converted into `T|null` ReflectionUnionType instances' => [
                new Node\NullableType(new Node\Identifier('string')),
                false,
                ReflectionUnionType::class,
                'string|null',
                true,
            ],
            [new Node\IntersectionType([new Node\Name('A'), new Node\Name('B')]), false, ReflectionIntersectionType::class, 'A&B', false],
            [new Node\UnionType([new Node\Name('A'), new Node\Name('B')]), false, ReflectionUnionType::class, 'A|B', false],
            'Union types composed of just `null` and a type are kept as `T|null` ReflectionUnionType' => [
                new Node\UnionType([new Node\Name('A'), new Node\Name('null')]),
                false,
                ReflectionUnionType::class,
                'A|null',
                true,
            ],
            'Union types composed of `null` and more than one type are kept as `T|U|null` ReflectionUnionType' => [
                new Node\UnionType([new Node\Name('A'), new Node\Name('B'), new Node\Name('null')]),
                false,
                ReflectionUnionType::class,
                'A|B|null',
                true,
            ],
            'Union type that allows `null` should contain `null` automatically' => [
                new Node\UnionType([new Node\Name('A'), new Node\Name('B')]),
                true,
                ReflectionUnionType::class,
                'A|B|null',
                true,
            ],
            'Don\'t add `null` to union type that allows `null` if it is already there' => [
                new Node\UnionType([new Node\Name('A'), new Node\Name('B'), new Node\Name('null')]),
                true,
                ReflectionUnionType::class,
                'A|B|null',
                true,
            ],
            [new Node\Name('null'), false, ReflectionNamedType::class, 'null', true],
            [new Node\Name('null'), true, ReflectionNamedType::class, 'null', true],
            [new Node\Name('mixed'), false, ReflectionNamedType::class, 'mixed', true],
            [new Node\Name('mixed'), true, ReflectionNamedType::class, 'mixed', true],
        ];
    }

    #[DataProvider('dataProvider')]
    public function test(
        Node\Identifier|Node\Name|Node\NullableType|Node\UnionType|Node\IntersectionType $node,
        bool $forceAllowsNull,
        string $expectedReflectionClass,
        string $expectedTypeAsString,
        bool $expectedAllowsNull,
    ): void {
        $reflectionType = ReflectionType::createFromNode($this->reflector, $this->owner, $node, $forceAllowsNull);

        self::assertInstanceOf($expectedReflectionClass, $reflectionType);
        self::assertSame($expectedTypeAsString, $reflectionType->__toString());
        self::assertSame($expectedAllowsNull, $reflectionType->allowsNull());
    }
}
