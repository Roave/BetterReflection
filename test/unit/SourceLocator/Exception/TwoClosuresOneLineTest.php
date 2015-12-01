<?php

namespace BetterReflectionTest\SourceLocator\Exception;

use BetterReflection\SourceLocator\Exception\TwoClosuresOneLine;
use SuperClosure\Exception\ClosureAnalysisException;
use PHPUnit_Framework_TestCase;

/**
 * @covers \BetterReflection\SourceLocator\Exception\TwoClosuresOneLine
 */
class TwoClosuresOneLineTest extends PHPUnit_Framework_TestCase
{
    public function testFromReflection()
    {
        $previous = new ClosureAnalysisException('Two closures were declared on the same line');

        $exception = TwoClosuresOneLine::fromClosureAnalysisException($previous);

        $this->assertInstanceOf(TwoClosuresOneLine::class, $exception);
        $this->assertSame('Two closures were declared on the same line', $exception->getMessage());
    }
}
