<?php

namespace Roave\BetterReflectionTest\Context;

use phpDocumentor\Reflection\Types\ContextFactory;
use Roave\BetterReflection\Context\PhpDocumentorContextFactory;
use phpDocumentor\Reflection\Types\Context;

class PhpDocumentorContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateForNamespace()
    {
        $namespace = 'Foobar';
        $source = <<<EOT
<?php 

namespace Foobar;

use Barfoo as Foo;
use Foobar as Boo;
EOT
        ;

        $innerContextFactory = new ContextFactory();

        $contextFactory = new PhpDocumentorContextFactory($innerContextFactory);
        $context = $contextFactory->createForNamespace($namespace, $source);
        $this->assertInstanceOf(Context::class, $context);
        $this->assertEquals('Foobar', $context->getNamespace());
        $this->assertEquals([
            'Foo' => 'Barfoo',
            'Boo' => 'Foobar',
        ], $context->getNamespaceAliases());
    }
}
