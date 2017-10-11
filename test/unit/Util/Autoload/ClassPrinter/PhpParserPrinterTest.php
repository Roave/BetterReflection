<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Util\Autoload\ClassPrinter;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter;
use Rector\BetterReflectionTest\Fixture\TestClassForPhpParserPrinterTest;

/**
 * @covers \Rector\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter
 */
final class PhpParserPrinterTest extends TestCase
{
    public function testPrinting() : void
    {
        self::assertSame(
            <<<'PHP'
namespace Rector\BetterReflectionTest\Fixture;

class TestClassForPhpParserPrinterTest
{
    public function foo() : \Rector\BetterReflection\TypesFinder\FindReturnType
    {
        return new \Rector\BetterReflection\TypesFinder\FindReturnType();
    }
}
PHP
            ,
            (new PhpParserPrinter())->__invoke(
                ReflectionClass::createFromName(TestClassForPhpParserPrinterTest::class)
            )
        );
    }
}
