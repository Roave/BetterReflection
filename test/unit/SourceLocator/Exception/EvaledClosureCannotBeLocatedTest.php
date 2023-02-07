<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Exception\EvaledClosureCannotBeLocated;

#[CoversClass(EvaledClosureCannotBeLocated::class)]
class EvaledClosureCannotBeLocatedTest extends TestCase
{
    public function testCreate(): void
    {
        $exception = EvaledClosureCannotBeLocated::create();

        self::assertInstanceOf(EvaledClosureCannotBeLocated::class, $exception);
        self::assertSame('Evaled closure cannot be located', $exception->getMessage());
    }
}
