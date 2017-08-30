<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutator;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutator\ReflectionParameterMutator;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutator\ReflectionParameterMutator
 */
class ReflectionParameterMutatorTest extends TestCase
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

    public function testMethodParameter() : void
    {
        $php = <<<'PHP'
<?php        
class Foo
{
    public function boo($a)
    {
    }
}    
PHP;

        $reflector           = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $parameterReflection = $reflector->reflect('Foo')->getMethod('boo')->getParameter('a');

        $node       = clone $parameterReflection->getAst();
        $node->name = 'b';

        $modifiedParameterReflection = (new ReflectionParameterMutator())->__invoke($parameterReflection, $node);

        self::assertInstanceOf(ReflectionParameter::class, $modifiedParameterReflection);
        self::assertNotSame($parameterReflection, $modifiedParameterReflection);
        self::assertSame($parameterReflection->getDeclaringFunction(), $modifiedParameterReflection->getDeclaringFunction());
        self::assertSame('b', $modifiedParameterReflection->getName());
    }
}
