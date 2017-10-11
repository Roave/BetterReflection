<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Util\Autoload\ClassLoaderMethod;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Util\Autoload\ClassLoaderMethod\EvalLoader;
use Rector\BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;

/**
 * @covers \Rector\BetterReflection\Util\Autoload\ClassLoaderMethod\EvalLoader
 */
final class EvalLoaderTest extends TestCase
{
    public function testEvalExecutes() : void
    {
        $reflectionClass = $this->createMock(ReflectionClass::class);

        $printer = $this->createMock(ClassPrinterInterface::class);
        $printer->expects(self::once())->method('__invoke')->with($reflectionClass)->willReturn('echo "hello world";');

        $evalLoader = new EvalLoader($printer);

        \ob_start();
        $evalLoader->__invoke($reflectionClass);
        $obContent = \ob_get_contents();
        \ob_end_clean();

        self::assertSame('hello world', $obContent);
    }
}
