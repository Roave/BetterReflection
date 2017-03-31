<?php

namespace Roave\BetterReflectionTest\SourceLocator\Exception;

use Roave\BetterReflection\SourceLocator\Exception\TwoClosuresOneLine;
use SuperClosure\Exception\ClosureAnalysisException;
use PHPUnit_Framework_TestCase;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Exception\TwoClosuresOneLine
 */
class TwoClosuresOneLineTest extends PHPUnit_Framework_TestCase
{
    public function testFromReflection()
    {
        $previous = new ClosureAnalysisException('Two closures were declared on the same line');

        $exception = TwoClosuresOneLine::fromClosureAnalysisException($previous);

        self::assertInstanceOf(TwoClosuresOneLine::class, $exception);
        self::assertSame('Two closures were declared on the same line', $exception->getMessage());
    }
}
