<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Factory\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingInstalledJson;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingInstalledJson
 */
class MissingInstalledJsonTest extends TestCase
{
    public function testInProjectPath() : void
    {
        self::assertSame(
            'Could not locate a "vendor/composer/installed.json" file in "foo/bar"',
            MissingInstalledJson::inProjectPath('foo/bar')
                ->getMessage(),
        );
    }
}
