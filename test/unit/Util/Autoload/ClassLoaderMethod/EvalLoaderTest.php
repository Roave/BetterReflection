<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\ClassLoaderMethod;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\EvalLoader;
use Roave\BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;

/**
 * @covers \Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\EvalLoader
 */
final class EvalLoaderTest extends \PHPUnit_Framework_TestCase
{
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
