<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionUnionType;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionUnionType
 */
class ReflectionUnionTypeTest extends TestCase
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
            [new Node\UnionType([new Node\Name('\A\Foo'), new Node\Name('Boo')]), '\A\Foo|Boo'],
            [new Node\UnionType([new Node\Name('A'), new Node\Name('B'), new Node\Identifier('null')]), 'A|B|null'],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function test(Node\UnionType $unionType, string $expectedString): void
    {
        $typeReflection = new ReflectionUnionType($this->reflector, $this->owner, $unionType, false);

        self::assertContainsOnlyInstancesOf(ReflectionNamedType::class, $typeReflection->getTypes());
        self::assertSame($expectedString, $typeReflection->__toString());
    }
}
