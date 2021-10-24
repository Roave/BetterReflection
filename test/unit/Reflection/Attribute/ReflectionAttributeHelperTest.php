<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use Roave\BetterReflection\Reflection\ReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @covers \Roave\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper
 */
class ReflectionAttributeHelperTest extends TestCase
{
    public function testCreateAttributes(): void
    {
        $ast             = $this->createMock(Node\Stmt\Class_::class);
        $ast->attrGroups = [
            new Node\AttributeGroup([
                new Node\Attribute(new Node\Name('SomeAttr')),
                new Node\Attribute(new Node\Name('AnotherAttr')),
            ]),
        ];

        $reflection = $this->createMock(ReflectionClass::class);
        $reflection
            ->method('getAst')
            ->willReturn($ast);

        $attributes = ReflectionAttributeHelper::createAttributes(
            $this->createMock(Reflector::class),
            $reflection,
        );

        self::assertCount(2, $attributes);
    }

    public function testFilterAttributesByName(): void
    {
        $attribute1 = $this->createMock(ReflectionAttribute::class);
        $attribute1
            ->method('getName')
            ->willReturn('SomeAttr');

        $attribute2 = $this->createMock(ReflectionAttribute::class);
        $attribute2
            ->method('getName')
            ->willReturn('AnotherAttr');

        $attribute3 = $this->createMock(ReflectionAttribute::class);
        $attribute3
            ->method('getName')
            ->willReturn('AnotherAttr');

        $attributes = [
            $attribute1,
            $attribute2,
            $attribute3,
        ];

        self::assertCount(1, ReflectionAttributeHelper::filterAttributesByName($attributes, 'SomeAttr'));
        self::assertCount(2, ReflectionAttributeHelper::filterAttributesByName($attributes, 'AnotherAttr'));
    }

    public function testFilterAttributesByInstance(): void
    {
        $attributeClass1 = $this->createMock(ReflectionClass::class);
        $attributeClass1
            ->method('getName')
            ->willReturn('ClassName');
        $attributeClass1
            ->method('isSubclassOf')
            ->willReturnMap([
                ['ParentClassName', true],
                ['InterfaceName', false],
            ]);
        $attributeClass1
            ->method('implementsInterface')
            ->willReturnMap([
                ['ParentClassName', false],
                ['InterfaceName', false],
            ]);

        $attribute1 = $this->createMock(ReflectionAttribute::class);
        $attribute1
            ->method('getClass')
            ->willReturn($attributeClass1);

        $attributeClass2 = $this->createMock(ReflectionClass::class);
        $attributeClass2
            ->method('getName')
            ->willReturn('Whatever');
        $attributeClass2
            ->method('isSubclassOf')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', false],
                ['InterfaceName', false],
            ]);
        $attributeClass2
            ->method('implementsInterface')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', false],
                ['InterfaceName', true],
            ]);

        $attribute2 = $this->createMock(ReflectionAttribute::class);
        $attribute2
            ->method('getClass')
            ->willReturn($attributeClass2);

        $attributeClass3 = $this->createMock(ReflectionClass::class);
        $attributeClass3
            ->method('getName')
            ->willReturn('Whatever');
        $attributeClass3
            ->method('isSubclassOf')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', true],
                ['InterfaceName', false],
            ]);
        $attributeClass3
            ->method('implementsInterface')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', false],
                ['InterfaceName', true],
            ]);

        $attribute3 = $this->createMock(ReflectionAttribute::class);
        $attribute3
            ->method('getClass')
            ->willReturn($attributeClass3);

        $attributes = [
            $attribute1,
            $attribute2,
            $attribute3,
        ];

        self::assertCount(1, ReflectionAttributeHelper::filterAttributesByInstance($attributes, 'ClassName'));
        self::assertCount(2, ReflectionAttributeHelper::filterAttributesByInstance($attributes, 'ParentClassName'));
        self::assertCount(2, ReflectionAttributeHelper::filterAttributesByInstance($attributes, 'InterfaceName'));
    }
}
