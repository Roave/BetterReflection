<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Util\FileHelper;

/**
 * @covers \Roave\BetterReflection\Util\FileHelper
 */
class FindHelperTest extends TestCase
{
    public function testNormalizeWindowsPath() : void
    {
        self::assertSame('directory/foo/boo/file.php', FileHelper::normalizeWindowsPath('directory\\foo/boo\\file.php'));
        self::assertSame('directory/foo/boo/file.php', FileHelper::normalizeWindowsPath('directory/foo/boo/file.php'));
    }
}
