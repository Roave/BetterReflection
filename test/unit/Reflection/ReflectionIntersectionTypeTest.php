<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\Reflector;

/** @covers \Roave\BetterReflection\Reflection\ReflectionIntersectionType */
class ReflectionIntersectionTypeTest extends TestCase
{
    private Reflector $reflector;
    private ReflectionParameter $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflector = $this->createMock(Reflector::class);
        $this->owner     = $this->createMock(ReflectionParameter::class);
    }

    /** @return list<array{0: Node\IntersectionType, 1: string}> */
    public function dataProvider(): array
    {
        return [
            [new Node\IntersectionType([new Node\Name('\A\Foo'), new Node\Name('Boo')]), '\A\Foo&Boo'],
            [new Node\IntersectionType([new Node\Name('A'), new Node\Name('B')]), 'A&B'],
        ];
    }

    /** @dataProvider dataProvider */
    public function test(Node\IntersectionType $intersectionType, string $expectedString): void
    {
        $typeReflection = new ReflectionIntersectionType($this->reflector, $this->owner, $intersectionType);

        self::assertContainsOnlyInstancesOf(ReflectionNamedType::class, $typeReflection->getTypes());
        self::assertSame($expectedString, $typeReflection->__toString());
        self::assertFalse($typeReflection->allowsNull());
    }

    public function testWithOwner(): void
    {
        $typeReflection = new ReflectionIntersectionType($this->reflector, $this->owner, new Node\IntersectionType([new Node\Name('\A\Foo'), new Node\Name('Boo')]));
        $types          = $typeReflection->getTypes();

        self::assertCount(2, $types);

        $owner = $this->createMock(ReflectionParameter::class);

        $cloneTypeReflection = $typeReflection->withOwner($owner);

        self::assertNotSame($typeReflection, $cloneTypeReflection);

        $cloneTypes = $cloneTypeReflection->getTypes();

        self::assertCount(2, $cloneTypes);
        self::assertNotSame($types[0], $cloneTypes[0]);
    }
}
