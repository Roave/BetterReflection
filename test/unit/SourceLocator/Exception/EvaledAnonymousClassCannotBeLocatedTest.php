<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Exception\EvaledAnonymousClassCannotBeLocated;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Exception\EvaledAnonymousClassCannotBeLocated
 */
class EvaledAnonymousClassCannotBeLocatedTest extends TestCase
{
    public function testCreate() : void
    {
        $exception = EvaledAnonymousClassCannotBeLocated::create();

        self::assertInstanceOf(EvaledAnonymousClassCannotBeLocated::class, $exception);
        self::assertSame('Evaled anonymous class cannot be located', $exception->getMessage());
    }
}
