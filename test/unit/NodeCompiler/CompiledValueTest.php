<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\NodeCompiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\NodeCompiler\CompiledValue;

#[CoversClass(CompiledValue::class)]
class CompiledValueTest extends TestCase
{
    public function testValuesHappyPath(): void
    {
        $compiledValue = new CompiledValue('value', 'constantName');

        self::assertSame('value', $compiledValue->value);
        self::assertSame('constantName', $compiledValue->constantName);
    }
}
