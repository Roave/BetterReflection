<?php

namespace Roave\BetterReflectionTest\Util;

use Roave\BetterReflection\Util\FileHelper;

/**
 * @covers \Roave\BetterReflection\Util\FileHelper
 */
class FindHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalizeWindowsPath() : void
    {
        self::assertSame('directory/foo/boo/file.php', FileHelper::normalizeWindowsPath('directory\\foo/boo\\file.php'));
        self::assertSame('directory/foo/boo/file.php', FileHelper::normalizeWindowsPath('directory/foo/boo/file.php'));
    }
}
