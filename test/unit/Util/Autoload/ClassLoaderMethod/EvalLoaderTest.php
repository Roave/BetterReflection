<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\ClassLoaderMethod;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\EvalLoader;
use Roave\BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;

use function ob_end_clean;
use function ob_get_contents;
use function ob_start;

/**
 * @covers \Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\EvalLoader
 */
final class EvalLoaderTest extends TestCase
{
    public function testEvalExecutes(): void
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
