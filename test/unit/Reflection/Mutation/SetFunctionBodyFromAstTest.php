<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Echo_;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\SetFunctionBodyFromAst;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use TypeError;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\SetFunctionBodyFromAst
 */
class SetFunctionBodyFromAstTest extends TestCase
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

    public function testInvalidAstThrowsException() : void
    {
        $php = '<?php function foo() {}';

        $reflector          = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect('foo');

        $this->expectException(TypeError::class);
        (new SetFunctionBodyFromAst())->__invoke($functionReflection, [1]);
    }

    public function testValidAst() : void
    {
        $php = '<?php function foo() {}';

        $reflector          = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect('foo');

        $modifiedFunctionReflection = (new SetFunctionBodyFromAst())->__invoke($functionReflection, [
            new Echo_([
                new String_('Hello world!'),
            ]),
        ]);

        self::assertInstanceOf(ReflectionFunction::class, $modifiedFunctionReflection);
        self::assertNotSame($functionReflection, $modifiedFunctionReflection);
        self::assertNotSame($modifiedFunctionReflection->getBodyCode(), $functionReflection->getBodyCode());
        self::assertSame("echo 'Hello world!';", $modifiedFunctionReflection->getBodyCode());
    }
}
