<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\RemoveClassMethod;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\RemoveClassMethod
 */
class RemoveClassMethodTest extends TestCase
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

    public function testRemove() : void
    {
        $php = <<<'PHP'
<?php
class Foo
{
    public function boo()
    {
    }
}
PHP;

        $reflector       = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflect('Foo');

        self::assertTrue($classReflection->hasMethod('boo'));

        $modifiedClassReflection = (new RemoveClassMethod())->__invoke($classReflection, 'boo');

        self::assertInstanceOf(ReflectionClass::class, $modifiedClassReflection);
        self::assertNotSame($classReflection, $modifiedClassReflection);
        self::assertLessThan(\count($classReflection->getMethods()), \count($modifiedClassReflection->getMethods()));
        self::assertCount(0, $modifiedClassReflection->getMethods());
        self::assertFalse($modifiedClassReflection->hasMethod('boo'));
    }
}
