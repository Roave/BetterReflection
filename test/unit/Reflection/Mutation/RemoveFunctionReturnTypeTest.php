<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\RemoveFunctionReturnType;
use Roave\BetterReflection\Reflection\Mutator\ReflectionFunctionAbstractMutator;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\Reflection\Mutator\ReflectionMutatorsSingleton;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\RemoveFunctionReturnType
 */
class RemoveFunctionReturnTypeTest extends TestCase
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

        $modifiedFunctionReflection = (new RemoveFunctionReturnType($this->functionMutator))->__invoke($functionReflection);

        self::assertInstanceOf(ReflectionFunction::class, $modifiedFunctionReflection);
        self::assertNotSame($functionReflection, $modifiedFunctionReflection);
        self::assertTrue($functionReflection->hasReturnType());
        self::assertFalse($modifiedFunctionReflection->hasReturnType());
        self::assertNull($modifiedFunctionReflection->getReturnType());
    }

    public function testWithoutReturnType() : void
    {
        $php = '<?php function boo() {}';

        $reflector          = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect('boo');

        $modifiedFunctionReflection = (new RemoveFunctionReturnType($this->functionMutator))->__invoke($functionReflection);

        self::assertInstanceOf(ReflectionFunction::class, $modifiedFunctionReflection);
        self::assertNotSame($functionReflection, $modifiedFunctionReflection);
        self::assertFalse($functionReflection->hasReturnType());
        self::assertFalse($modifiedFunctionReflection->hasReturnType());
        self::assertNull($modifiedFunctionReflection->getReturnType());
    }
}
