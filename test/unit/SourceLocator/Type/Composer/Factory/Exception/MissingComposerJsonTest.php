<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Factory\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingComposerJson;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingComposerJson
 */
class MissingComposerJsonTest extends TestCase
{
    public function testInProjectPath() : void
    {
        self::assertSame(
            'Could not locate a "composer.json" file in "foo/bar"',
            MissingComposerJson::inProjectPath('foo/bar')
                ->getMessage(),
        );
    }
}
