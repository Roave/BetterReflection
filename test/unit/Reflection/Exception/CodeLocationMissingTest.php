<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\CodeLocationMissing;

#[CoversClass(CodeLocationMissing::class)]
class CodeLocationMissingTest extends TestCase
{
    public function testCreate(): void
    {
        $exception = CodeLocationMissing::create();

        self::assertInstanceOf(CodeLocationMissing::class, $exception);
        self::assertSame('Code location is missing', $exception->getMessage());
    }
}
