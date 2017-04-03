<?php

namespace Roave\BetterReflectionTest\NodeCompiler;

use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\NodeCompiler\CompilerContext
 */
class CompilerContextTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatingContextWithoutSelf() : void
    {
        $reflector = new ClassReflector(new StringSourceLocator('<?php'));
        $context = new CompilerContext($reflector, null);

        self::assertFalse($context->hasSelf());
        self::assertSame($reflector, $context->getReflector());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The current context does not have a class for self');
        $context->getSelf();
    }

    public function testCreatingContextWithSelf() : void
    {
        $reflector = new ClassReflector(new StringSourceLocator('<?php class Foo {}'));
        $self = $reflector->reflect('Foo');

        $context = new CompilerContext($reflector, $self);

        self::assertTrue($context->hasSelf());
        self::assertSame($reflector, $context->getReflector());
        self::assertSame($self, $context->getSelf());
    }
}
