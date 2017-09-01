<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod as CoreReflectionMethod;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\AddClassMethod;
use Roave\BetterReflection\Reflection\Mutator\ReflectionClassMutator;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\Reflection\Mutator\ReflectionMutatorsSingleton;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\AddClassMethod
 */
class AddClassMethodTest extends TestCase
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

        $reflector       = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflect('Foo');

        self::assertFalse($classReflection->hasMethod('boo'));

        $modifiedClassReflection = (new AddClassMethod($this->classMutator))->__invoke($classReflection, 'boo', CoreReflectionMethod::IS_PUBLIC | CoreReflectionMethod::IS_FINAL);

        self::assertInstanceOf(ReflectionClass::class, $modifiedClassReflection);
        self::assertNotSame($classReflection, $modifiedClassReflection);
        self::assertGreaterThan(\count($classReflection->getMethods()), \count($modifiedClassReflection->getMethods()));
        self::assertCount(1, $modifiedClassReflection->getMethods());
        self::assertTrue($modifiedClassReflection->hasMethod('boo'));

        $methodReflection = $modifiedClassReflection->getMethod('boo');

        self::assertTrue($methodReflection->isPublic());
        self::assertTrue($methodReflection->isFinal());
    }

    public function testAddThrowsExceptionWhenInvalidVisibility() : void
    {
        $php = '<?php class Foo {}';

        $classReflection = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        $this->expectException(InvalidArgumentException::class);

        (new AddClassMethod($this->classMutator))->__invoke($classReflection, 'boo', 999999999);
    }
}
