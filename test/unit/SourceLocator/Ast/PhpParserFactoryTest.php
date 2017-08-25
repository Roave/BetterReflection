<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Ast;

use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Ast\PhpParserFactory;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Ast\PhpParserFactory
 */
class PhpParserFactoryTest extends TestCase
{
    public function testCreate() : void
    {
        self::assertInstanceOf(Parser::class, PhpParserFactory::create());
    }
}
