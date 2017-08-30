<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\SetPropertyVisibility;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflectionTest\Fixture\ExampleClass;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\SetPropertyVisibility
 */
class SetPropertyVisibilityTest extends TestCase
{
    /**
     * @var ClassReflector
     */
    private $classReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $this->classReflector = (new BetterReflection())->classReflector();
    }

    public function testInvalidVisibilityThrowsException() : void
    {
        $classReflection    = $this->classReflector->reflect(ExampleClass::class);
        $propertyReflection = $classReflection->getProperty('publicProperty');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Visibility should be \ReflectionProperty::IS_PRIVATE, ::IS_PROTECTED or ::IS_PUBLIC constants');
        (new SetPropertyVisibility())->__invoke($propertyReflection, 0);
    }

    public function testValidVisibility() : void
    {
        $classReflection    = $this->classReflector->reflect(ExampleClass::class);
        $propertyReflection = $classReflection->getProperty('publicStaticProperty');

        self::assertFalse($propertyReflection->isPrivate(), 'Should initially be public, was private');
        self::assertFalse($propertyReflection->isProtected(), 'Should initially be public, was protected');
        self::assertTrue($propertyReflection->isPublic(), 'Should initially be public, was not public');
        self::assertTrue($propertyReflection->isStatic(), 'Should initially be static');

        $propertyModifiedToPrivate = (new SetPropertyVisibility())->__invoke($propertyReflection, CoreReflectionProperty::IS_PRIVATE);

        self::assertNotSame($propertyReflection, $propertyModifiedToPrivate);
        self::assertTrue($propertyModifiedToPrivate->isPrivate(), 'After setting private, isPrivate is not set');
        self::assertFalse($propertyModifiedToPrivate->isProtected(), 'After setting private, protected is now set but should not be');
        self::assertFalse($propertyModifiedToPrivate->isPublic(), 'After setting private, public is still set but should not be');
        self::assertTrue($propertyModifiedToPrivate->isStatic(), 'Should still be static after setting private');

        $propertyModifiedToProtected = (new SetPropertyVisibility())->__invoke($propertyReflection, CoreReflectionProperty::IS_PROTECTED);

        self::assertNotSame($propertyReflection, $propertyModifiedToProtected);
        self::assertFalse($propertyModifiedToProtected->isPrivate(), 'After setting protected, should no longer be private');
        self::assertTrue($propertyModifiedToProtected->isProtected(), 'After setting protected, expect isProtected to be set');
        self::assertFalse($propertyModifiedToProtected->isPublic(), 'After setting protected, public is set but should not be');
        self::assertTrue($propertyModifiedToProtected->isStatic(), 'Should still be static after setting protected');

        $propertyModifiedToPublic = (new SetPropertyVisibility())->__invoke($propertyReflection, CoreReflectionProperty::IS_PUBLIC);

        self::assertNotSame($propertyReflection, $propertyModifiedToPublic);
        self::assertFalse($propertyModifiedToPublic->isPrivate(), 'After setting public, isPrivate should not be set');
        self::assertFalse($propertyModifiedToPublic->isProtected(), 'After setting public, isProtected should not be set');
        self::assertTrue($propertyModifiedToPublic->isPublic(), 'After setting public, isPublic should be set but was not');
        self::assertTrue($propertyModifiedToPublic->isStatic(), 'Should still be static after setting public');
    }
}
