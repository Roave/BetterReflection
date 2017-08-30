<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\AddFunctionParameter;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\AddFunctionParameter
 */
class AddFunctionParameterTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    /**
     * @var ClassReflector
     */
    private $classReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator     = (new BetterReflection())->astLocator();
        $this->classReflector = $this->createMock(ClassReflector::class);
    }

    public function testAdd() : void
    {
        $php = '<?php function foo() {}';

        $reflector          = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect('foo');

        $modifiedFunctionReflection = (new AddFunctionParameter())->__invoke($functionReflection, 'newParameterName');

        self::assertInstanceOf(ReflectionFunction::class, $modifiedFunctionReflection);
        self::assertNotSame($functionReflection, $modifiedFunctionReflection);
        self::assertGreaterThan($functionReflection->getNumberOfParameters(), $modifiedFunctionReflection->getNumberOfParameters());
        self::assertSame(1, $modifiedFunctionReflection->getNumberOfParameters());
        self::assertInstanceOf(ReflectionParameter::class, $modifiedFunctionReflection->getParameter('newParameterName'));
    }
}
