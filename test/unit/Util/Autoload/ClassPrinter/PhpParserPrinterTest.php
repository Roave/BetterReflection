<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\ClassPrinter;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter;
use Roave\BetterReflectionTest\Fixture\TestClassForPhpParserPrinterTest;

/**
 * @covers \Roave\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter
 */
final class PhpParserPrinterTest extends TestCase
{
    public function testPrinting() : void
    {
        self::assertSame(
            <<<'PHP'
namespace Roave\BetterReflectionTest\Fixture;

use Roave\BetterReflection\TypesFinder\FindTypeFromAst;
class TestClassForPhpParserPrinterTest
{
    public function foo() : FindTypeFromAst
    {
        return new FindTypeFromAst();
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
