<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\SourceLocator\Exception\TwoClosuresOnSameLine;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Exception\TwoClosuresOnSameLine
 */
class TwoClosuresOnSameLineTest extends TestCase
{
    public function testCreate() : void
    {
        $exception = TwoClosuresOnSameLine::create('foo.php', 123);

        self::assertInstanceOf(TwoClosuresOnSameLine::class, $exception);
        self::assertSame('Two closures on line 123 in foo.php', $exception->getMessage());
    }
}
