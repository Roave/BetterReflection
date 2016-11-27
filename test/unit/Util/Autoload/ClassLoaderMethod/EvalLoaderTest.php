<?php

namespace BetterReflectionTest\Util\Autoload\ClassLoaderMethod;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Util\Autoload\ClassLoaderMethod\EvalLoader;
use BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;

/**
 * @covers \BetterReflection\Util\Autoload\ClassLoaderMethod\EvalLoader
 */
class EvalLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * // testEvalExecutesEvenThoughWeProbablyDoNotWantItTo
     */
    public function testEvalExecutes()
    {
        $reflectionClass = $this->createMock(ReflectionClass::class);

        $printer = $this->createMock(ClassPrinterInterface::class);
        $printer->expects(self::once())->method('__invoke')->with($reflectionClass)->willReturn('echo "hello world";');

        $evalLoader = new EvalLoader($printer);

        ob_start();
        $evalLoader->__invoke($reflectionClass);
        $obContent = ob_get_contents();
        ob_end_clean();

        self::assertSame('hello world', $obContent);
    }
}
