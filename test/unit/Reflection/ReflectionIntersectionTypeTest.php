<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionIntersectionType
 */
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

    public function dataProvider(): array
    {
        return [
            [new Node\IntersectionType([new Node\Name('\A\Foo'), new Node\Name('Boo')]), '\A\Foo&Boo'],
            [new Node\IntersectionType([new Node\Name('A'), new Node\Name('B')]), 'A&B'],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function test(Node\IntersectionType $intersectionType, string $expectedString): void
    {
        $typeReflection = new ReflectionIntersectionType($this->reflector, $this->owner, $intersectionType);

        self::assertContainsOnlyInstancesOf(ReflectionNamedType::class, $typeReflection->getTypes());
        self::assertSame($expectedString, $typeReflection->__toString());
    }
}
