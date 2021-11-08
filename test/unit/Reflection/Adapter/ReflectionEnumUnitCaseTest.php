<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionEnumUnitCase as CoreReflectionEnumUnitCase;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionEnum as ReflectionEnumAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase as ReflectionEnumUnitCaseAdapter;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionEnum as BetterReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionEnumCase as BetterReflectionEnumCase;

use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase
 */
class ReflectionEnumUnitCaseTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionEnumUnitCase::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionEnumUnitCaseAdapterReflection = new CoreReflectionClass(ReflectionEnumUnitCaseAdapter::class);

        self::assertTrue($reflectionEnumUnitCaseAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionEnumUnitCaseAdapter::class, $reflectionEnumUnitCaseAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    public function methodExpectationProvider(): array
    {
        return [
            // Inherited
            ['__toString', null, '', []],
            ['getName', null, '', []],
            ['getValue', NotImplemented::class, null, []],
            ['getDeclaringClass', null, $this->createMock(BetterReflectionClass::class), []],
            ['getDocComment', null, '', []],
            ['getAttributes', null, [], []],
        ];
    }

    /**
     * @param list<mixed> $args
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, ?string $expectedException, mixed $returnValue, array $args): void
    {
        $reflectionStub = $this->createMock(BetterReflectionEnumCase::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionEnumUnitCaseAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testIsPublic(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertTrue($reflectionEnumUnitCaseAdapter->isPublic());
    }

    public function testIsProtected(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertFalse($reflectionEnumUnitCaseAdapter->isProtected());
    }

    public function testIsPrivate(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertFalse($reflectionEnumUnitCaseAdapter->isPrivate());
    }

    public function testGetModifiers(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertSame(ReflectionEnumUnitCaseAdapter::IS_PUBLIC, $reflectionEnumUnitCaseAdapter->getModifiers());
    }

    public function testGetAttributes(): void
    {
        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getName')
            ->willReturn('SomeAttribute');
        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getName')
            ->willReturn('AnotherAttribute');

        $betterReflectionAttributes = [$betterReflectionAttribute1, $betterReflectionAttribute2];

        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);
        $betterReflectionEnumCase
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);
        $attributes                    = $reflectionEnumUnitCaseAdapter->getAttributes();

        self::assertCount(2, $attributes);
        self::assertSame('SomeAttribute', $attributes[0]->getName());
        self::assertSame('AnotherAttribute', $attributes[1]->getName());
    }

    public function testGetAttributesWithName(): void
    {
        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getName')
            ->willReturn('SomeAttribute');
        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getName')
            ->willReturn('AnotherAttribute');

        $betterReflectionAttributes = [$betterReflectionAttribute1, $betterReflectionAttribute2];

        $betterReflectionEnumCase = $this->getMockBuilder(BetterReflectionEnumCase::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionEnumCase
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);
        $attributes                    = $reflectionEnumUnitCaseAdapter->getAttributes('SomeAttribute');

        self::assertCount(1, $attributes);
        self::assertSame('SomeAttribute', $attributes[0]->getName());
    }

    public function testGetAttributesWithInstance(): void
    {
        $betterReflectionAttributeClass1 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionAttributeClass1
            ->method('getName')
            ->willReturn('ClassName');
        $betterReflectionAttributeClass1
            ->method('isSubclassOf')
            ->willReturnMap([
                ['ParentClassName', true],
                ['InterfaceName', false],
            ]);
        $betterReflectionAttributeClass1
            ->method('implementsInterface')
            ->willReturnMap([
                ['ParentClassName', false],
                ['InterfaceName', false],
            ]);

        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getClass')
            ->willReturn($betterReflectionAttributeClass1);

        $betterReflectionAttributeClass2 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionAttributeClass2
            ->method('getName')
            ->willReturn('Whatever');
        $betterReflectionAttributeClass2
            ->method('isSubclassOf')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', false],
                ['InterfaceName', false],
            ]);
        $betterReflectionAttributeClass2
            ->method('implementsInterface')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', false],
                ['InterfaceName', true],
            ]);

        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getClass')
            ->willReturn($betterReflectionAttributeClass2);

        $betterReflectionAttributeClass3 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionAttributeClass3
            ->method('getName')
            ->willReturn('Whatever');
        $betterReflectionAttributeClass3
            ->method('isSubclassOf')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', true],
                ['InterfaceName', false],
            ]);
        $betterReflectionAttributeClass3
            ->method('implementsInterface')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', false],
                ['InterfaceName', true],
            ]);

        $betterReflectionAttribute3 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute3
            ->method('getClass')
            ->willReturn($betterReflectionAttributeClass3);

        $betterReflectionAttributes = [
            $betterReflectionAttribute1,
            $betterReflectionAttribute2,
            $betterReflectionAttribute3,
        ];

        $betterReflectionEnumCase = $this->getMockBuilder(BetterReflectionEnumCase::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionEnumCase
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertCount(1, $reflectionEnumUnitCaseAdapter->getAttributes('ClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionEnumUnitCaseAdapter->getAttributes('ParentClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionEnumUnitCaseAdapter->getAttributes('InterfaceName', ReflectionAttributeAdapter::IS_INSTANCEOF));
    }

    public function testIsFinal(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertTrue($reflectionEnumUnitCaseAdapter->isFinal());
    }

    public function testIsEnumCase(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertTrue($reflectionEnumUnitCaseAdapter->isEnumCase());
    }

    public function testGetEnum(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);

        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);
        $betterReflectionEnumCase
            ->method('getDeclaringEnum')
            ->willReturn($betterReflectionEnum);

        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertInstanceOf(ReflectionEnumAdapter::class, $reflectionEnumUnitCaseAdapter->getEnum());
    }
}