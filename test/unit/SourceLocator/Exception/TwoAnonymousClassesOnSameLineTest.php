<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Exception;

use Roave\BetterReflection\SourceLocator\Exception\TwoAnonymousClassesOnSameLine;
use PHPUnit_Framework_TestCase;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Exception\TwoAnonymousClassesOnSameLine
 */
class TwoAnonymousClassesOnSameLineTest extends PHPUnit_Framework_TestCase
{
    public function testCreate() : void
    {
        $exception = TwoAnonymousClassesOnSameLine::create('foo.php', 123);

        self::assertInstanceOf(TwoAnonymousClassesOnSameLine::class, $exception);
        self::assertSame('Two anonymous classes on line 123 in foo.php', $exception->getMessage());
    }
}
