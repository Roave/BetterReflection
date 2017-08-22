<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Ast;

use PhpParser\Parser;
use Roave\BetterReflection\SourceLocator\Ast\PhpParserFactory;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Ast\PhpParserFactory
 */
class PhpParserFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate() : void
    {
        self::assertInstanceOf(Parser::class, PhpParserFactory::create());
    }
}
