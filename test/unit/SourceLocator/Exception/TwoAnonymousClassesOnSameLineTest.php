<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\SourceLocator\Exception\TwoAnonymousClassesOnSameLine;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Exception\TwoAnonymousClassesOnSameLine
 */
class TwoAnonymousClassesOnSameLineTest extends TestCase
{
    public function testCreate() : void
    {
        $exception = TwoAnonymousClassesOnSameLine::create('foo.php', 123);

        self::assertInstanceOf(TwoAnonymousClassesOnSameLine::class, $exception);
        self::assertSame('Two anonymous classes on line 123 in foo.php', $exception->getMessage());
    }
}
