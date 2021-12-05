<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionType
 */
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

    public function dataProvider(): array
    {
        return [
            [new Node\Name('A'), false, ReflectionNamedType::class, false],
            [new Node\Identifier('string'), false, ReflectionNamedType::class, false],
            [new Node\Identifier('string'), true, ReflectionNamedType::class, true],
            [new Node\NullableType(new Node\Identifier('string')), false, ReflectionNamedType::class, true],
            [new Node\IntersectionType([new Node\Name('A'), new Node\Name('B')]), false, ReflectionIntersectionType::class, false],
            [new Node\UnionType([new Node\Name('A'), new Node\Name('B')]), false, ReflectionUnionType::class, false],
            'Union types composed of just `null` and a type are simplified into a ReflectionNamedType' => [
                new Node\UnionType([new Node\Name('A'), new Node\Name('null')]),
                false,
                ReflectionNamedType::class,
                true,
            ],
            'Union types composed of `null` and more than one type are kept as ReflectionUnionType' => [
                new Node\UnionType([new Node\Name('A'), new Node\Name('B'), new Node\Name('null')]),
                false,
                ReflectionUnionType::class,
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function test(
        Node\Identifier|Node\Name|Node\NullableType|Node\UnionType|Node\IntersectionType $node,
        bool $forceAllowsNull,
        string $expectedReflectionClass,
        bool $expectedAllowsNull,
    ): void {
        $reflectionType = ReflectionType::createFromNode($this->reflector, $this->owner, $node, $forceAllowsNull);

        self::assertInstanceOf($expectedReflectionClass, $reflectionType);
        self::assertSame($expectedAllowsNull, $reflectionType->allowsNull());
    }
}
