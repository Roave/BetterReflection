<?php

namespace BetterReflectionTest\NodeCompiler;

use BetterReflection\NodeCompiler\CompilerContext;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\StringSourceLocator;

/**
 * @covers \BetterReflection\NodeCompiler\CompilerContext
 */
class CompilerContextTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatingContextWithoutSelf()
    {
        $reflector = new ClassReflector(new StringSourceLocator('<?php'));
        $context = new CompilerContext($reflector, null);

        $this->assertFalse($context->hasSelf());
        $this->assertSame($reflector, $context->getReflector());

        $this->setExpectedException(
            \RuntimeException::class,
            'The current context does not have a class for self'
        );
        $context->getSelf();
    }

    public function testCreatingContextWithSelf()
    {
        $reflector = new ClassReflector(new StringSourceLocator('<?php class Foo {}'));
        $self = $reflector->reflect('Foo');

        $context = new CompilerContext($reflector, $self);

        $this->assertTrue($context->hasSelf());
        $this->assertSame($reflector, $context->getReflector());
        $this->assertSame($self, $context->getSelf());
    }
}
