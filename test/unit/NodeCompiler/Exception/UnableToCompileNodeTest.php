<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\NodeCompiler\Exception;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function sprintf;

/**
 * @covers \Roave\BetterReflection\NodeCompiler\Exception\UnableToCompileNode
 */
final class UnableToCompileNodeTest extends TestCase
{
    public function testDefaults(): void
    {
        $exception = new UnableToCompileNode();

        self::assertNull($exception->constantName());
    }

    /** @dataProvider supportedContextTypes */
    public function testBecauseOfNotFoundConstantReference(CompilerContext $context, string $contextName): void
    {
        $constantName = 'FOO';

        $exception = UnableToCompileNode::becauseOfNotFoundConstantReference(
            $context,
            new ConstFetch(new Name($constantName)),
            $constantName,
        );

        self::assertSame(
            sprintf(
                'Could not locate constant "%s" while evaluating expression in %s in file "" (line -1)',
                $constantName,
                $contextName,
            ),
            $exception->getMessage(),
        );

        self::assertSame($constantName, $exception->constantName());
    }

    /** @dataProvider supportedContextTypes */
    public function testBecauseOfNotFoundClassConstantReference(CompilerContext $context, string $contextName): void
    {
        $targetClass = $this->createMock(ReflectionClass::class);

        $targetClass
            ->expects(self::any())
            ->method('getName')
            ->willReturn('An\\Example');

        self::assertSame(
            sprintf(
                'Could not locate constant An\Example::SOME_CONSTANT while trying to evaluate constant expression in %s in file "" (line -1)',
                $contextName,
            ),
            UnableToCompileNode::becauseOfNotFoundClassConstantReference(
                $context,
                $targetClass,
                new ClassConstFetch(
                    new Name\FullyQualified('A'),
                    new Identifier('SOME_CONSTANT'),
                ),
            )->getMessage(),
        );
    }

    /** @dataProvider supportedContextTypes */
    public function testForUnRecognizedExpressionInContext(CompilerContext $context, string $contextName): void
    {
        self::assertSame(
            sprintf(
                'Unable to compile expression in %s: unrecognized node type %s in file "" (line -1)',
                $contextName,
                Yield_::class,
            ),
            UnableToCompileNode::forUnRecognizedExpressionInContext(
                new Yield_(new String_('')),
                $context,
            )->getMessage(),
        );
    }

    /** @return CompilerContext[] */
    public function supportedContextTypes(): array
    {
        $php = <<<'PHP'
<?php

namespace Foo;

const SOME_CONSTANT = 'some_constant';

class SomeClass
{
    public function someMethod()
    {
    }
}

function someFunction()
{
}
PHP;

        $astLocator = BetterReflectionSingleton::instance()->astLocator();
        $reflector  = new DefaultReflector(new AggregateSourceLocator([
            new StringSourceLocator($php, $astLocator),
            new PhpInternalSourceLocator($astLocator, BetterReflectionSingleton::instance()->sourceStubber()),
        ]));

        $class          = $reflector->reflectClass('Foo\SomeClass');
        $method         = $class->getMethod('someMethod');
        $function       = $reflector->reflectFunction('Foo\someFunction');
        $constant       = $reflector->reflectConstant('Foo\SOME_CONSTANT');
        $globalConstant = $reflector->reflectConstant('PHP_VERSION_ID');

        return [
            [new CompilerContext($reflector, $globalConstant), 'global namespace'],
            [new CompilerContext($reflector, $constant), 'namespace Foo'],
            [new CompilerContext($reflector, $class), 'class Foo\SomeClass'],
            [new CompilerContext($reflector, $method), 'method Foo\SomeClass::someMethod()'],
            [new CompilerContext($reflector, $function), 'function Foo\someFunction()'],
        ];
    }
}
