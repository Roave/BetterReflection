<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\RemoveFunctionParameter;
use Roave\BetterReflection\Reflection\Mutator\ReflectionFunctionAbstractMutator;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\Reflection\Mutator\ReflectionMutatorsSingleton;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\RemoveFunctionParameter
 */
class RemoveFunctionParameterTest extends TestCase
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

    public function testRemove() : void
    {
        $php = '<?php function foo($a, $b) {}';

        $reflector          = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect('foo');

        $modifiedFunctionReflection = (new RemoveFunctionParameter($this->functionMutator))->__invoke($functionReflection, 'a');

        self::assertInstanceOf(ReflectionFunction::class, $modifiedFunctionReflection);
        self::assertNotSame($functionReflection, $modifiedFunctionReflection);
        self::assertLessThan($functionReflection->getNumberOfParameters(), $modifiedFunctionReflection->getNumberOfParameters());
        self::assertSame(1, $modifiedFunctionReflection->getNumberOfParameters());
        self::assertNull($modifiedFunctionReflection->getParameter('a'));
    }
}
