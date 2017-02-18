<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\ClassPrinter;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter;
use Roave\BetterReflectionTest\Fixture\TestClassForPhpParserPrinterTest;

/**
 * @covers \Roave\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter
 */
final class PhpParserPrinterTest extends \PHPUnit_Framework_TestCase
{
    public function testPrinting()
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
