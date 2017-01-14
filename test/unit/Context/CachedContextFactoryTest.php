<?php

namespace Roave\BetterReflectionTest\Context;

use Roave\BetterReflection\Context\ContextFactory;
use Roave\BetterReflection\Context\CachedContextFactory;
use phpDocumentor\Reflection\Types\Context;

class CachedContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextFactory
     */
    private $innerFactory;

    public function setUp()
    {
        $this->innerFactory = $this->createMock(ContextFactory::class);
    }

    public function testFactoryShouldOnlyCallInnerFactoryOnce()
    {
        $cachedContextFactory = new CachedContextFactory($this->innerFactory);

        $this->innerFactory
            ->expects($this->once())
            ->method('createForNamespace')
            ->will($this->returnCallback(function ($namespace, $source) {
                $this->assertEquals('__source__', $source);
                $this->assertEquals('Foobar', $namespace);
                return new Context($namespace, []);
            }));

        $cachedContextFactory->createForNamespace('Foobar', '__source__');
        $cachedContextFactory->createForNamespace('Foobar', '__source__');
    }
}
