<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Exception\TwoClosuresOnSameLine;

#[CoversClass(TwoClosuresOnSameLine::class)]
class TwoClosuresOnSameLineTest extends TestCase
{
    public function testCreate(): void
    {
        $exception = TwoClosuresOnSameLine::create('foo.php', 123);

        self::assertInstanceOf(TwoClosuresOnSameLine::class, $exception);
        self::assertSame('Two closures on line 123 in foo.php', $exception->getMessage());
    }
}
