<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\SetFunctionBodyFromString;
use Roave\BetterReflection\Reflection\Mutator\ReflectionFunctionAbstractMutator;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\Reflection\Mutator\ReflectionMutatorsSingleton;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\SetFunctionBodyFromString
 */
class SetFunctionBodyFromStringTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    /**
     * @var Parser
     */
    private $parser;

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

        $betterReflection = new BetterReflection();

        $this->astLocator      = $betterReflection->astLocator();
        $this->parser          = $betterReflection->phpParser();
        $this->classReflector  = $this->createMock(ClassReflector::class);
        $this->functionMutator = ReflectionMutatorsSingleton::instance()->functionMutator();
    }

    public function testString() : void
    {
        $php = '<?php function foo() {}';

        $reflector          = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect('foo');

        $string = "echo 'Hello world!';";

        $modifiedFunctionReflection = (new SetFunctionBodyFromString($this->parser, $this->functionMutator))->__invoke($functionReflection, $string);

        self::assertInstanceOf(ReflectionFunction::class, $modifiedFunctionReflection);
        self::assertNotSame($functionReflection, $modifiedFunctionReflection);
        self::assertNotSame($modifiedFunctionReflection->getBodyCode(), $functionReflection->getBodyCode());
        self::assertSame($string, $modifiedFunctionReflection->getBodyCode());
    }
}
