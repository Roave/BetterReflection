<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutator;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutator\ReflectionPropertyMutator;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutator\ReflectionPropertyMutator
 */
class ReflectionPropertyMutatorTest extends TestCase
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

    public function testMutate() : void
    {
        $php = <<<'PHP'
<?php        
class Foo
{
    private $boo;
}    
PHP;

        $reflector          = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $propertyReflection = $reflector->reflect('Foo')->getProperty('boo');

        $node                 = clone $propertyReflection->getAst();
        $node->props[0]->name = 'ooo';

        $modifiedPropertyReflection = (new ReflectionPropertyMutator())->__invoke($propertyReflection, $node);

        self::assertInstanceOf(ReflectionProperty::class, $modifiedPropertyReflection);
        self::assertNotSame($propertyReflection, $modifiedPropertyReflection);
        self::assertSame($propertyReflection->getDeclaringClass(), $modifiedPropertyReflection->getDeclaringClass());
        self::assertSame('ooo', $modifiedPropertyReflection->getName());
    }
}
