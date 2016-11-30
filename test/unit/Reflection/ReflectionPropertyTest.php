<?php

namespace Roave\BetterReflectionTest\Reflection;

use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\Fixture\ClassForHinting;
use phpDocumentor\Reflection\Types;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionProperty
 */
class ReflectionPropertyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassReflector
     */
    private $reflector;

    public function setUp()
    {
        global $loader;
        $this->reflector = new ClassReflector(new ComposerSourceLocator($loader));
    }

    public function testCreateFromName()
    {
        $property = ReflectionProperty::createFromName(\ReflectionFunctionAbstract::class, 'name');

        $this->assertInstanceOf(ReflectionProperty::class, $property);
        $this->assertSame('name', $property->getName());
    }

    public function testCreateFromInstance()
    {
        $property = ReflectionProperty::createFromInstance(new ClassForHinting(), 'someProperty');

        $this->assertInstanceOf(ReflectionProperty::class, $property);
        $this->assertSame('someProperty', $property->getName());
    }

    public function testImplementsReflector()
    {
        $classInfo = $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass');
        $publicProp = $classInfo->getProperty('publicProperty');

        $this->assertInstanceOf(\Reflector::class, $publicProp);
    }

    public function testVisibilityMethods()
    {
        $classInfo = $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass');

        $privateProp = $classInfo->getProperty('privateProperty');
        $this->assertTrue($privateProp->isPrivate());

        $protectedProp = $classInfo->getProperty('protectedProperty');
        $this->assertTrue($protectedProp->isProtected());

        $publicProp = $classInfo->getProperty('publicProperty');
        $this->assertTrue($publicProp->isPublic());
    }

    public function testIsStatic()
    {
        $classInfo = $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass');

        $publicProp = $classInfo->getProperty('publicProperty');
        $this->assertFalse($publicProp->isStatic());

        $staticProp = $classInfo->getProperty('publicStaticProperty');
        $this->assertTrue($staticProp->isStatic());
    }

    /**
     * @return array
     */
    public function stringTypesDataProvider()
    {
        return [
            ['privateProperty', ['int', 'float', '\stdClass']],
            ['protectedProperty', ['bool', 'bool[]', 'bool[][]']],
            ['publicProperty', ['string']],
        ];
    }

    /**
     * @param string $propertyName
     * @param string[] $expectedTypes
     * @dataProvider stringTypesDataProvider
     */
    public function testGetDocBlockTypeStrings($propertyName, $expectedTypes)
    {
        $classInfo = $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass');

        $property = $classInfo->getProperty($propertyName);

        $this->assertSame($expectedTypes, $property->getDocBlockTypeStrings());
    }

    /**
     * @return array
     */
    public function typesDataProvider()
    {
        return [
            ['privateProperty', [Types\Integer::class, Types\Float_::class, Types\Object_::class]],
            ['protectedProperty', [Types\Boolean::class, Types\Array_::class, Types\Array_::class]],
            ['publicProperty', [Types\String_::class]],
        ];
    }

    /**
     * @param string $propertyName
     * @param string[] $expectedTypes
     * @dataProvider typesDataProvider
     */
    public function testGetDocBlockTypes($propertyName, $expectedTypes)
    {
        $classInfo = $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass');

        $foundTypes = $classInfo->getProperty($propertyName)->getDocBlockTypes();

        $this->assertCount(count($expectedTypes), $foundTypes);

        foreach ($expectedTypes as $i => $expectedType) {
            $this->assertInstanceOf($expectedType, $foundTypes[$i]);
        }
    }

    public function testGetDocComment()
    {
        $expectedDoc = "/**\n * @var string\n */";

        $classInfo = $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass');
        $property = $classInfo->getProperty('publicProperty');

        $this->assertSame($expectedDoc, $property->getDocComment());
    }

    public function testGetDocCommentReturnsEmptyStringWithNoComment()
    {
        $classInfo = $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass');
        $property = $classInfo->getProperty('publicStaticProperty');

        $this->assertSame('', $property->getDocComment());
    }

    public function testExportThrowsException()
    {
        $this->expectException(\Exception::class);
        ReflectionProperty::export();
    }

    public function modifierProvider()
    {
        return [
            ['publicProperty', \ReflectionProperty::IS_PUBLIC, ['public']],
            ['protectedProperty', \ReflectionProperty::IS_PROTECTED, ['protected']],
            ['privateProperty', \ReflectionProperty::IS_PRIVATE, ['private']],
            ['publicStaticProperty', \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_STATIC, ['public', 'static']],
        ];
    }

    /**
     * @param string $propertyName
     * @param int $expectedModifier
     * @param string[] $expectedModifierNames
     * @dataProvider modifierProvider
     */
    public function testGetModifiers($propertyName, $expectedModifier, array $expectedModifierNames)
    {
        $classInfo = $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass');
        $property = $classInfo->getProperty($propertyName);

        $this->assertSame($expectedModifier, $property->getModifiers());
        $this->assertSame(
            $expectedModifierNames,
            \Reflection::getModifierNames($property->getModifiers())
        );
    }

    public function testIsDefault()
    {
        $classInfo = $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass');

        $this->assertTrue($classInfo->getProperty('publicProperty')->isDefault());
        $this->assertTrue($classInfo->getProperty('publicStaticProperty')->isDefault());
    }

    public function testIsDefaultWithRuntimeDeclaredProperty()
    {
        $this->assertFalse(
            ReflectionProperty::createFromNode(
                $this->reflector,
                new Property(Class_::MODIFIER_PUBLIC, [new PropertyProperty('foo')]),
                $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass'),
                false
            )
            ->isDefault()
        );
    }

    public function castToStringProvider()
    {
        return [
            ['publicProperty', 'Property [ <default> public $publicProperty ]'],
            ['protectedProperty', 'Property [ <default> protected $protectedProperty ]'],
            ['privateProperty', 'Property [ <default> private $privateProperty ]'],
            ['publicStaticProperty', 'Property [ public static $publicStaticProperty ]'],
        ];
    }

    /**
     * @param string $propertyName
     * @param string $expectedString
     * @dataProvider castToStringProvider
     */
    public function testCastingToString($propertyName, $expectedString)
    {
        $classInfo = $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass');
        $this->assertSame($expectedString, (string)$classInfo->getProperty($propertyName));
    }

    public function testGetDefaultProperty()
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/DefaultProperties.php')))->reflect('Foo');

        $this->assertSame(123, $classInfo->getProperty('hasDefault')->getDefaultValue());
        $this->assertNull($classInfo->getProperty('noDefault')->getDefaultValue());
    }

    public function testCannotClone()
    {
        $classInfo = $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass');
        $publicProp = $classInfo->getProperty('publicProperty');

        $this->expectException(Uncloneable::class);
        $unused = clone $publicProp;
    }

    public function testSetVisibility()
    {
        $classInfo = $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass');
        $publicProp = $classInfo->getProperty('publicStaticProperty');

        $this->assertFalse($publicProp->isPrivate(), 'Should initially be public, was private');
        $this->assertFalse($publicProp->isProtected(), 'Should initially be public, was protected');
        $this->assertTrue($publicProp->isPublic(), 'Should initially be public, was not public');
        $this->assertTrue($publicProp->isStatic(), 'Should initially be static');

        $publicProp->setVisibility(\ReflectionProperty::IS_PRIVATE);

        $this->assertTrue($publicProp->isPrivate(), 'After setting private, isPrivate is not set');
        $this->assertFalse($publicProp->isProtected(), 'After setting private, protected is now set but should not be');
        $this->assertFalse($publicProp->isPublic(), 'After setting private, public is still set but should not be');
        $this->assertTrue($publicProp->isStatic(), 'Should still be static after setting private');

        $publicProp->setVisibility(\ReflectionProperty::IS_PROTECTED);

        $this->assertFalse($publicProp->isPrivate(), 'After setting protected, should no longer be private');
        $this->assertTrue($publicProp->isProtected(), 'After setting protected, expect isProtected to be set');
        $this->assertFalse($publicProp->isPublic(), 'After setting protected, public is set but should not be');
        $this->assertTrue($publicProp->isStatic(), 'Should still be static after setting protected');

        $publicProp->setVisibility(\ReflectionProperty::IS_PUBLIC);

        $this->assertFalse($publicProp->isPrivate(), 'After setting public, isPrivate should not be set');
        $this->assertFalse($publicProp->isProtected(), 'After setting public, isProtected should not be set');
        $this->assertTrue($publicProp->isPublic(), 'After setting public, isPublic should be set but was not');
        $this->assertTrue($publicProp->isStatic(), 'Should still be static after setting public');
    }

    public function testSetVisibilityThrowsExceptionWithInvalidArgument()
    {
        $classInfo = $this->reflector->reflect('\Roave\BetterReflectionTest\Fixture\ExampleClass');
        $publicProp = $classInfo->getProperty('publicProperty');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Visibility should be \ReflectionProperty::IS_PRIVATE, ::IS_PROTECTED or ::IS_PUBLIC constants');
        $publicProp->setVisibility('foo');
    }
}
