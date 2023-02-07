<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\SourceStubber\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\SourceStubber\Exception\CouldNotFindPhpStormStubs;

#[CoversClass(CouldNotFindPhpStormStubs::class)]
class CouldNotFindPhpStormStubsTest extends TestCase
{
    public function testCreate(): void
    {
        $exception = CouldNotFindPhpStormStubs::create();

        self::assertInstanceOf(CouldNotFindPhpStormStubs::class, $exception);
        self::assertSame('Could not find PhpStorm stubs', $exception->getMessage());
    }
}
