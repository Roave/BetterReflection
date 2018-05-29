<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\NodeCompiler\Exception;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Roave\BetterReflection\NodeCompiler\Exception\UnableToCompileNode
 */
final class UnableToCompileNodeTest extends TestCase
{
    /** @dataProvider supportedContextTypes */
    public function testBecauseOfOfNotFoundConstantReference(CompilerContext $context) : void
    {
        $contextName = $context->hasSelf() ? 'EmptyClass' : 'unknown context';

        self::assertSame(
            'Could not locate constant "FOO" while evaluating expression in ' . $contextName . ' at line -1',
            UnableToCompileNode::becauseOfOfNotFoundConstantReference(
                $context,
                new ConstFetch(new Name('FOO'))
            )->getMessage()
        );
    }

    /** @dataProvider supportedContextTypes */
    public function testBecauseOfNotFoundClassConstantReference(CompilerContext $context) : void
    {
        $contextName = $context->hasSelf() ? 'EmptyClass' : 'unknown context';

        /** @var ReflectionClass|MockObject $targetClass */
        $targetClass = $this->createMock(ReflectionClass::class);

        $targetClass
            ->expects(self::any())
            ->method('getName')
            ->willReturn('An\\Example');

        self::assertSame(
            'Could not locate constant An\Example::SOME_CONSTANT while trying to evaluate constant expression in '
            . $contextName . ' at line -1',
            UnableToCompileNode::becauseOfNotFoundClassConstantReference(
                $context,
                $targetClass,
                new ClassConstFetch(
                    new Name\FullyQualified('A'),
                    new Identifier('SOME_CONSTANT')
                )
            )->getMessage()
        );
    }

    /** @dataProvider supportedContextTypes */
    public function testForUnRecognizedExpressionInContext(CompilerContext $context) : void
    {
        $contextName = $context->hasSelf() ? 'EmptyClass' : 'unknown context';

        self::assertSame(
            'Unable to compile expression in ' . $contextName
            . ': unrecognized node type ' . Yield_::class
            . ' at line -1',
            UnableToCompileNode::forUnRecognizedExpressionInContext(
                new Yield_(new String_('')),
                $context
            )->getMessage()
        );
    }

    /** @return CompilerContext[] */
    public function supportedContextTypes() : array
    {
        $reflector = new ClassReflector(new StringSourceLocator(
            '<?php class EmptyClass {}',
            BetterReflectionSingleton::instance()->astLocator()
        ));

        return [
            [new CompilerContext($reflector, null)],
            [new CompilerContext($reflector, $reflector->reflect('EmptyClass'))],
        ];
    }
}
