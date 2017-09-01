<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutator;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutator\ReflectionFunctionAbstractMutator;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutator\ReflectionFunctionAbstractMutator
 */
class ReflectionFunctionAbstractMutatorTest extends TestCase
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

    public function testMethod() : void
    {
        $php = <<<'PHP'
<?php        
class Foo
{
    public function boo() : void
    {
    }
}    
PHP;

        $reflector        = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $methodReflection = $reflector->reflect('Foo')->getMethod('boo');

        $node       = clone $methodReflection->getAst();
        $node->name = 'ooo';

        $modifiedMethodReflection = (new ReflectionFunctionAbstractMutator())->__invoke($methodReflection, $node);

        self::assertInstanceOf(ReflectionMethod::class, $modifiedMethodReflection);
        self::assertNotSame($methodReflection, $modifiedMethodReflection);
        self::assertSame($methodReflection->getDeclaringClass(), $modifiedMethodReflection->getDeclaringClass());
        self::assertSame('ooo', $modifiedMethodReflection->getShortName());
    }

    public function testFunction() : void
    {
        $php = <<<'PHP'
<?php        
function boo() : void
{
}
PHP;

        $reflector          = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect('boo');

        $node       = clone $functionReflection->getAst();
        $node->name = 'ooo';

        $modifiedFunctionReflection = (new ReflectionFunctionAbstractMutator())->__invoke($functionReflection, $node);

        self::assertInstanceOf(ReflectionFunction::class, $modifiedFunctionReflection);
        self::assertNotSame($functionReflection, $modifiedFunctionReflection);
        self::assertSame($functionReflection->getNamespaceName(), $modifiedFunctionReflection->getNamespaceName());
        self::assertSame('ooo', $modifiedFunctionReflection->getShortName());
    }

    public function testFunctionInNamespace() : void
    {
        $php = <<<'PHP'
<?php
namespace Foo;
        
function boo() : void
{
}
PHP;

        $reflector          = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect('Foo\boo');

        $node       = clone $functionReflection->getAst();
        $node->name = 'ooo';

        $modifiedFunctionReflection = (new ReflectionFunctionAbstractMutator())->__invoke($functionReflection, $node);

        self::assertInstanceOf(ReflectionFunction::class, $modifiedFunctionReflection);
        self::assertNotSame($functionReflection, $modifiedFunctionReflection);
        self::assertSame($functionReflection->getNamespaceName(), $modifiedFunctionReflection->getNamespaceName());
        self::assertSame('ooo', $modifiedFunctionReflection->getShortName());
    }
}
