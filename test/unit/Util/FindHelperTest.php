<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Util;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Util\FileHelper;

/**
 * @covers \Rector\BetterReflection\Util\FileHelper
 */
class FindHelperTest extends TestCase
{
    public function testNormalizeWindowsPath() : void
    {
        self::assertSame('directory/foo/boo/file.php', FileHelper::normalizeWindowsPath('directory\\foo/boo\\file.php'));
        self::assertSame('directory/foo/boo/file.php', FileHelper::normalizeWindowsPath('directory/foo/boo/file.php'));
    }
}
