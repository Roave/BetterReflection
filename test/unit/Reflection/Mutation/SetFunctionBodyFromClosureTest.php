<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\SetFunctionBodyFromClosure;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\SetFunctionBodyFromClosure
 */
class SetFunctionBodyFromClosureTest extends TestCase
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

    protected function setUp() : void
    {
        parent::setUp();

        $betterReflection = new BetterReflection();

        $this->astLocator     = $betterReflection->astLocator();
        $this->parser         = $betterReflection->phpParser();
        $this->classReflector = $this->createMock(ClassReflector::class);
    }

    public function testClosure() : void
    {
        $php = '<?php function foo() {}';

        $reflector          = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect('foo');

        $closure = function () : void {
            echo 'Hello world!';
        };

        $modifiedFunctionReflection = (new SetFunctionBodyFromClosure($this->parser))->__invoke($functionReflection, $closure);

        self::assertInstanceOf(ReflectionFunction::class, $modifiedFunctionReflection);
        self::assertNotSame($functionReflection, $modifiedFunctionReflection);
        self::assertNotSame($modifiedFunctionReflection->getBodyCode(), $functionReflection->getBodyCode());
        self::assertSame("echo 'Hello world!';", $modifiedFunctionReflection->getBodyCode());
    }
}
