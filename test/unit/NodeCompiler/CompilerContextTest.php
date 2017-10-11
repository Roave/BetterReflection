<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\NodeCompiler;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\NodeCompiler\CompilerContext;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\Reflector\FunctionReflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Rector\BetterReflection\Util\FileHelper;
use Rector\BetterReflectionTest\BetterReflectionSingleton;
use RuntimeException;

/**
 * @covers \Rector\BetterReflection\NodeCompiler\CompilerContext
 */
class CompilerContextTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function testCreatingContextWithoutSelf() : void
    {
        $reflector = new ClassReflector(new StringSourceLocator('<?php', $this->astLocator));
        $context   = new CompilerContext($reflector, null);

        self::assertFalse($context->hasSelf());
        self::assertSame($reflector, $context->getReflector());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The current context does not have a class for self');
        $context->getSelf();
    }

    public function testCreatingContextWithSelf() : void
    {
        $reflector = new ClassReflector(new StringSourceLocator('<?php class Foo {}', $this->astLocator));
        $self      = $reflector->reflect('Foo');

        $context = new CompilerContext($reflector, $self);

        self::assertTrue($context->hasSelf());
        self::assertSame($reflector, $context->getReflector());
        self::assertSame($self, $context->getSelf());
    }

    public function testGetFileName() : void
    {
        $filename = FileHelper::normalizeWindowsPath(__DIR__ . '/CompilerContextTest.php');

        $reflector = new ClassReflector(new SingleFileSourceLocator($filename, $this->astLocator));
        $self      = $reflector->reflect(self::class);

        $context = new CompilerContext($reflector, $self);

        self::assertSame($filename, $context->getFileName());
    }

    public function testGetFileNameWithoutSelf() : void
    {
        $filename = __DIR__ . '/CompilerContextTest.php';

        $reflector = new ClassReflector(new SingleFileSourceLocator($filename, $this->astLocator));
        $context   = new CompilerContext($reflector, null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The current context does not have a class for self');
        $context->getFileName();
    }

    public function testClassMagicConstantAsDefaultValueFromClass() : void
    {
        $phpCode = '<?php
        namespace Foo;
        
        class Bar {
            public $property = __CLASS__;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Foo\Bar');
        self::assertSame('Foo\Bar', $classInfo->getProperty('property')->getDefaultValue());
    }

    public function testClassMagicConstantAsDefaultValueFromFunction() : void
    {
        $phpCode = '<?php
        namespace Foo;
        
        function baz($parameter = __CLASS__)
        {
        }
        ';

        $reflector    = new FunctionReflector(
            new StringSourceLocator($phpCode, $this->astLocator),
            BetterReflectionSingleton::instance()->classReflector()
        );
        $functionInfo = $reflector->reflect('Foo\baz');
        self::assertSame('', $functionInfo->getParameter('parameter')->getDefaultValue());
    }
}
