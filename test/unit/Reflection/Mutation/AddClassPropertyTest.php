<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\AddClassProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\AddClassProperty
 */
class AddClassPropertyTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = (new BetterReflection())->astLocator();
    }

    public function testAdd() : void
    {
        $php = '<?php class Foo {}';

        $classReflection = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        self::assertFalse($classReflection->hasProperty('bar'));

        $modifiedClassReflectionWithPublicProperty = (new AddClassProperty())->__invoke($classReflection, 'publicBar', CoreReflectionProperty::IS_PUBLIC, false);

        self::assertTrue($modifiedClassReflectionWithPublicProperty->hasProperty('publicBar'));
        self::assertTrue($modifiedClassReflectionWithPublicProperty->getProperty('publicBar')->isPublic());

        $modifiedClassReflectionWithProtectedProperty = (new AddClassProperty())->__invoke($classReflection, 'protectedBar', CoreReflectionProperty::IS_PROTECTED, false);

        self::assertTrue($modifiedClassReflectionWithProtectedProperty->hasProperty('protectedBar'));
        self::assertTrue($modifiedClassReflectionWithProtectedProperty->getProperty('protectedBar')->isProtected());

        $modifiedClassReflectionWithPrivateProperty = (new AddClassProperty())->__invoke($classReflection, 'privateBar', CoreReflectionProperty::IS_PRIVATE, false);

        self::assertTrue($modifiedClassReflectionWithPrivateProperty->hasProperty('privateBar'));
        self::assertTrue($modifiedClassReflectionWithPrivateProperty->getProperty('privateBar')->isPrivate());

        $modifiedClassReflectionWithStaticProperty = (new AddClassProperty())->__invoke($classReflection, 'staticBar', CoreReflectionProperty::IS_PUBLIC, true);

        self::assertTrue($modifiedClassReflectionWithStaticProperty->hasProperty('staticBar'));
        self::assertTrue($modifiedClassReflectionWithStaticProperty->getProperty('staticBar')->isStatic());
    }

    public function testAddThrowsExceptionWhenInvalidVisibility() : void
    {
        $php = '<?php class Foo {}';

        $classReflection = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        $this->expectException(InvalidArgumentException::class);

        (new AddClassProperty())->__invoke($classReflection, 'public', 999999999, false);
    }
}
