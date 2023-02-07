<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Factory\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\InvalidProjectDirectory;

#[CoversClass(InvalidProjectDirectory::class)]
class InvalidProjectDirectoryTest extends TestCase
{
    public function testAtPath(): void
    {
        self::assertSame(
            'Could not locate project directory "foo/bar"',
            InvalidProjectDirectory::atPath('foo/bar')
                ->getMessage(),
        );
    }
}
