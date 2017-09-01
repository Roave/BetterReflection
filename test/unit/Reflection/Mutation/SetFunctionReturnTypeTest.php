<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\SetFunctionReturnType;
use Roave\BetterReflection\Reflection\Mutator\ReflectionFunctionAbstractMutator;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\Reflection\Mutator\ReflectionMutatorsSingleton;
use Traversable;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\SetFunctionReturnType
 */
class SetFunctionReturnTypeTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    /**
     * @var ClassReflector
     */
    private $classReflector;

    /**
     * @var ReflectionFunctionAbstractMutator
     */
    private $functionMutator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator      = (new BetterReflection())->astLocator();
        $this->classReflector  = $this->createMock(ClassReflector::class);
        $this->functionMutator = ReflectionMutatorsSingleton::instance()->functionMutator();
    }

    public function testWithReturnType() : void
    {
        $php = '<?php function boo() : void {}';

        $reflector          = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect('boo');

        $modifiedFunctionReflection = (new SetFunctionReturnType($this->functionMutator))->__invoke($functionReflection, 'int', false);

        self::assertInstanceOf(ReflectionFunction::class, $modifiedFunctionReflection);
        self::assertNotSame($functionReflection, $modifiedFunctionReflection);
        self::assertTrue($modifiedFunctionReflection->hasReturnType());
        self::assertNotSame((string) $functionReflection->getReturnType(), (string) $modifiedFunctionReflection->getReturnType());
        self::assertSame('int', (string) $modifiedFunctionReflection->getReturnType());
        self::assertFalse($modifiedFunctionReflection->getReturnType()->allowsNull());
    }

    public function testWithoutReturnType() : void
    {
        $php = '<?php function boo() {}';

        $reflector          = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect('boo');

        $modifiedFunctionReflection = (new SetFunctionReturnType($this->functionMutator))->__invoke($functionReflection, 'bool', false);

        self::assertInstanceOf(ReflectionFunction::class, $modifiedFunctionReflection);
        self::assertNotSame($functionReflection, $modifiedFunctionReflection);
        self::assertTrue($modifiedFunctionReflection->hasReturnType());
        self::assertSame('bool', (string) $modifiedFunctionReflection->getReturnType());
        self::assertFalse($modifiedFunctionReflection->getReturnType()->allowsNull());
    }

    public function testNullableReturnType() : void
    {
        $php = '<?php function boo() {}';

        $reflector          = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect('boo');

        $modifiedFunctionReflection = (new SetFunctionReturnType($this->functionMutator))->__invoke($functionReflection, 'string', true);

        self::assertInstanceOf(ReflectionFunction::class, $modifiedFunctionReflection);
        self::assertNotSame($functionReflection, $modifiedFunctionReflection);
        self::assertTrue($modifiedFunctionReflection->hasReturnType());
        self::assertSame('string', (string) $modifiedFunctionReflection->getReturnType());
        self::assertTrue($modifiedFunctionReflection->getReturnType()->allowsNull());
    }

    public function testClassReturnType() : void
    {
        $php = '<?php function boo() {}';

        $reflector          = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect('boo');

        $modifiedFunctionReflection = (new SetFunctionReturnType($this->functionMutator))->__invoke($functionReflection, Traversable::class, false);

        self::assertInstanceOf(ReflectionFunction::class, $modifiedFunctionReflection);
        self::assertNotSame($functionReflection, $modifiedFunctionReflection);
        self::assertTrue($modifiedFunctionReflection->hasReturnType());
        self::assertSame(Traversable::class, (string) $modifiedFunctionReflection->getReturnType());
    }
}
