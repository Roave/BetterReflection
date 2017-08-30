<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutator;

use PhpParser\Node\Name\FullyQualified;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutator\ReflectionClassMutator;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutator\ReflectionClassMutator
 */
class ReflectionClassMutatorTest extends TestCase
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

    public function testWithoutNamespace() : void
    {
        $php = <<<'PHP'
<?php        
class Foo
{
}    
PHP;

        $reflector       = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflect('Foo');

        $node                 = clone $classReflection->getAst();
        $node->name           = 'Ooo';
        $node->namespacedName = 'Ooo';

        $modifiedClassReflection = (new ReflectionClassMutator())->__invoke($classReflection, $node);

        self::assertInstanceOf(ReflectionClass::class, $modifiedClassReflection);
        self::assertNotSame($classReflection, $modifiedClassReflection);
        self::assertSame($classReflection->getNamespaceName(), $classReflection->getNamespaceName());
        self::assertSame('Ooo', $modifiedClassReflection->getShortName());
        self::assertSame('Ooo', $modifiedClassReflection->getName());
    }

    public function testInNamespace() : void
    {
        $php = <<<'PHP'
<?php        
namespace Boo;

class Foo
{
}    
PHP;

        $reflector       = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflect('Boo\Foo');

        $node                 = clone $classReflection->getAst();
        $node->name           = 'Ooo';
        $node->namespacedName = new FullyQualified('Boo\Ooo');

        $modifiedClassReflection = (new ReflectionClassMutator())->__invoke($classReflection, $node);

        self::assertInstanceOf(ReflectionClass::class, $modifiedClassReflection);
        self::assertNotSame($classReflection, $modifiedClassReflection);
        self::assertSame($classReflection->getNamespaceName(), $classReflection->getNamespaceName());
        self::assertSame('Ooo', $modifiedClassReflection->getShortName());
        self::assertSame('Boo\Ooo', $modifiedClassReflection->getName());
    }
}
