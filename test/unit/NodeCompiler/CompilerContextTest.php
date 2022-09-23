<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\NodeCompiler;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\ReflectionEnum;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/** @covers \Roave\BetterReflection\NodeCompiler\CompilerContext */
class CompilerContextTest extends TestCase
{
    private Locator $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function testCreatingContextFromClass(): void
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

    public function testCreatingContextFromProperty(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

class Boo
{
    public $baz;
}
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $class     = $reflector->reflectClass('Foo\Boo');
        $property  = $class->getProperty('baz');

        $context = new CompilerContext($reflector, $property);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertSame($class, $context->getClass());
        self::assertNull($context->getFunction());
    }

    public function testCreatingContextFromClassConstant(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

class Boo
{
    public const BAZ = 'baz';
}
PHP;

        $reflector     = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $class         = $reflector->reflectClass('Foo\Boo');
        $classConstant = $class->getReflectionConstant('BAZ');

        $context = new CompilerContext($reflector, $classConstant);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertSame($class, $context->getClass());
        self::assertNull($context->getFunction());
    }

    public function testCreatingContextFromEnumCase(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

enum Boo
{
    case BAZ;
}
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $enum      = $reflector->reflectClass('Foo\Boo');

        self::assertInstanceOf(ReflectionEnum::class, $enum);

        $enumCase = $enum->getCase('BAZ');

        $context = new CompilerContext($reflector, $enumCase);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertSame($enum, $context->getClass());
        self::assertNull($context->getFunction());
    }

    public function testCreatingContextFromMethod(): void
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
        $method    = $class->getMethod('baz');

        $context = new CompilerContext($reflector, $method);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertSame($class, $context->getClass());
        self::assertSame($method, $context->getFunction());
    }

    public function testCreatingContextFromFunction(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

function baz($parameter = __CLASS__)
{
}
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $function  = $reflector->reflectFunction('Foo\baz');

        $context = new CompilerContext($reflector, $function);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertNull($context->getClass());
        self::assertSame($function, $context->getFunction());
    }

    public function testCreatingContextFromParameter(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

function baz($parameter = __CLASS__)
{
}
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $function  = $reflector->reflectFunction('Foo\baz');
        $parameter = $function->getParameter('parameter');

        $context = new CompilerContext($reflector, $parameter);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertNull($context->getClass());
        self::assertSame($function, $context->getFunction());
    }

    public function testCreatingContextFromConstant(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

const BAZ = 'baz';
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $constant  = $reflector->reflectConstant('Foo\BAZ');

        $context = new CompilerContext($reflector, $constant);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertNull($context->getClass());
        self::assertNull($context->getFunction());
    }

    public function testCreatingContextWithoutNamespace(): void
    {
        $phpCode = <<<'PHP'
<?php

const BAZ = 'baz';
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $constant  = $reflector->reflectConstant('BAZ');

        $context = new CompilerContext($reflector, $constant);

        self::assertNull($context->getNamespace());
    }
}
