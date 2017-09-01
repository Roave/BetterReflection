<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\AddClassProperty;
use Roave\BetterReflection\Reflection\Mutator\ReflectionClassMutator;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\Reflection\Mutator\ReflectionMutatorsSingleton;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\AddClassProperty
 */
class AddClassPropertyTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    /**
     * @var ReflectionClassMutator
     */
    private $classMutator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator   = (new BetterReflection())->astLocator();
        $this->classMutator = ReflectionMutatorsSingleton::instance()->classMutator();
    }

    public function testAdd() : void
    {
        $php = '<?php class Foo {}';

        $classReflection = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        self::assertFalse($classReflection->hasProperty('bar'));

        $addClassProperty = new AddClassProperty($this->classMutator);

        $modifiedClassReflectionWithPublicProperty = $addClassProperty->__invoke($classReflection, 'publicBar', CoreReflectionProperty::IS_PUBLIC, false);

        self::assertTrue($modifiedClassReflectionWithPublicProperty->hasProperty('publicBar'));
        self::assertTrue($modifiedClassReflectionWithPublicProperty->getProperty('publicBar')->isPublic());

        $modifiedClassReflectionWithProtectedProperty = $addClassProperty->__invoke($classReflection, 'protectedBar', CoreReflectionProperty::IS_PROTECTED, false);

        self::assertTrue($modifiedClassReflectionWithProtectedProperty->hasProperty('protectedBar'));
        self::assertTrue($modifiedClassReflectionWithProtectedProperty->getProperty('protectedBar')->isProtected());

        $modifiedClassReflectionWithPrivateProperty = $addClassProperty->__invoke($classReflection, 'privateBar', CoreReflectionProperty::IS_PRIVATE, false);

        self::assertTrue($modifiedClassReflectionWithPrivateProperty->hasProperty('privateBar'));
        self::assertTrue($modifiedClassReflectionWithPrivateProperty->getProperty('privateBar')->isPrivate());

        $modifiedClassReflectionWithStaticProperty = $addClassProperty->__invoke($classReflection, 'staticBar', CoreReflectionProperty::IS_PUBLIC, true);

        self::assertTrue($modifiedClassReflectionWithStaticProperty->hasProperty('staticBar'));
        self::assertTrue($modifiedClassReflectionWithStaticProperty->getProperty('staticBar')->isStatic());
    }

    public function testAddThrowsExceptionWhenInvalidVisibility() : void
    {
        $php = '<?php class Foo {}';

        $classReflection = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        $this->expectException(InvalidArgumentException::class);

        (new AddClassProperty($this->classMutator))->__invoke($classReflection, 'public', 999999999, false);
    }
}
