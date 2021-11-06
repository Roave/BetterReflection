<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use LogicException;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionEnum;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function sprintf;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionNamedType
 */
class ReflectionNamedTypeTest extends TestCase
{
    private Locator $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astLocator = (new BetterReflection())->astLocator();
    }

    public function testGetClassFromPropertyType(): void
    {
        $php = '<?php
            class Foo {
                public self $property;
            }
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $classReflection    = $reflector->reflectClass('Foo');
        $propertyReflection = $classReflection->getProperty('property');
        $typeReflection     = $propertyReflection->getType();
        $class              = $typeReflection->getClass();

        self::assertSame('Foo', $class->getName());
    }

    public function testGetClassFromFunctionReturnType(): void
    {
        $php = '<?php
            class Foo {}
            function getFoo(): Foo {}
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $functionReflection = $reflector->reflectFunction('getFoo');
        $typeReflection     = $functionReflection->getReturnType();
        $class              = $typeReflection->getClass();

        self::assertSame('Foo', $class->getName());
    }

    public function testGetClassFromMethodReturnType(): void
    {
        $php = '<?php
            abstract class Foo {
                public function method(): self;
            }
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $classReflection  = $reflector->reflectClass('Foo');
        $methodReflection = $classReflection->getMethod('method');
        $typeReflection   = $methodReflection->getReturnType();
        $class            = $typeReflection->getClass();

        self::assertSame('Foo', $class->getName());
    }

    public function testGetClassFromMethodParameterType(): void
    {
        $php = '<?php
            abstract class Foo {
                public function method(self $parameter);
            }
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $classReflection     = $reflector->reflectClass('Foo');
        $methodReflection    = $classReflection->getMethod('method');
        $parameterReflection = $methodReflection->getParameter('parameter');
        $typeReflection      = $parameterReflection->getType();
        $class               = $typeReflection->getClass();

        self::assertSame('Foo', $class->getName());
    }

    public function testGetClassFromEnumBackingTypeThrowsException(): void
    {
        $php = '<?php
            enum Foo: int {}
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $enumReflection = $reflector->reflectClass('Foo');

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $typeReflection = $enumReflection->getBackingType();

        self::expectException(LogicException::class);
        $typeReflection->getClass();
    }

    public function testGetClassFromFunctionWithSelfReturnTypeThrowsException(): void
    {
        $php = '<?php
            function getSelf(): self {}
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $functionReflection = $reflector->reflectFunction('getSelf');
        $typeReflection     = $functionReflection->getReturnType();

        self::expectException(LogicException::class);
        $typeReflection->getClass();
    }

    public function testGetClassFromParameterWithSelfTypeInFunctionThrowsException(): void
    {
        $php = '<?php
            function withSelf(self $parameter) {}
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $functionReflection  = $reflector->reflectFunction('withSelf');
        $parameterReflection = $functionReflection->getParameter('parameter');
        $typeReflection      = $parameterReflection->getType();

        self::expectException(LogicException::class);
        $typeReflection->getClass();
    }

    public function dataGetClassWithSelfOrStatic(): array
    {
        return [
            ['ParentClass', 'self', 'ParentClass'],
            ['ParentClass', 'static', 'ParentClass'],
            ['ClassWithExtend', 'self', 'ParentClass'],
            ['ClassWithExtend', 'static', 'ClassWithExtend'],
        ];
    }

    /**
     * @dataProvider dataGetClassWithSelfOrStatic
     */
    public function testGetClassWithSelfOrStatic(string $classNameToReflect, string $type, string $typeClassName): void
    {
        $php = sprintf('<?php
            abstract class ParentClass {
                public function method(): %s;
            }
            
            class ClassWithExtend extends ParentClass {}
        ', $type);

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $classReflection  = $reflector->reflectClass($classNameToReflect);
        $methodReflection = $classReflection->getMethod('method');

        $typeReflection = $methodReflection->getReturnType();
        $class          = $typeReflection->getClass();

        self::assertSame($typeClassName, $class->getName());
    }

    public function testGetClassWithParent(): void
    {
        $php = '<?php
            abstract class Foo {
            }
            
            class Boo extends Foo {
                public function method(): parent {}
            }
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $classReflection  = $reflector->reflectClass('Boo');
        $methodReflection = $classReflection->getMethod('method');
        $typeReflection   = $methodReflection->getReturnType();
        $class            = $typeReflection->getClass();

        self::assertSame('Foo', $class->getName());
    }

    public function testGetClassThrowsExceptionForBuiltinType(): void
    {
        $php = '<?php
            abstract class Foo {
                public function method(): int;
            }
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $classReflection  = $reflector->reflectClass('Foo');
        $methodReflection = $classReflection->getMethod('method');
        $typeReflection   = $methodReflection->getReturnType();

        self::expectException(LogicException::class);
        $typeReflection->getClass();
    }
}
