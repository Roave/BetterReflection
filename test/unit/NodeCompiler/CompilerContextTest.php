<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\NodeCompiler;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Roave\BetterReflection\NodeCompiler\CompilerContext
 */
class CompilerContextTest extends TestCase
{
    private Locator $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function testCreatingContext(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

class Boo
{
    public function baz($parameter = __CLASS__)
    {
    }
}
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $class     = $reflector->reflectClass('Foo\Boo');

        $context = new CompilerContext($reflector, $class);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertSame($class, $context->getClass());
        self::assertNull($context->getFunction());
    }

    public function testCreatingContextWithoutClass(): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator('<?php function foo() {}', $this->astLocator));
        $context   = new CompilerContext($reflector, $reflector->reflectFunction('foo'));

        self::assertNull($context->getClass());
    }

    public function testCreatingContextWithoutFunction(): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator('<?php class Foo {}', $this->astLocator));
        $context   = new CompilerContext($reflector, $reflector->reflectClass('Foo'));

        self::assertNull($context->getFunction());
    }

    public function testClassMagicConstantAsDefaultValueFromClass(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

class Bar {
    public $property = __CLASS__;
}
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflectClass('Foo\Bar');

        self::assertSame('Foo\Bar', $classInfo->getProperty('property')->getDefaultValue());
    }

    public function testClassMagicConstantAsDefaultValueFromFunction(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

function baz($parameter = __CLASS__)
{
}
PHP;

        $reflector    = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $functionInfo = $reflector->reflectFunction('Foo\baz');
        self::assertSame('', $functionInfo->getParameter('parameter')->getDefaultValue());
    }
}
