<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest;

use Rector\BetterReflection\BetterReflection;

abstract class BetterReflectionSingleton
{
    /**
     * @var BetterReflection|null
     */
    private static $betterReflection;

    final private function __construct()
    {
    }

    public static function instance() : BetterReflection
    {
        return self::$betterReflection ?? self::$betterReflection = new BetterReflection();
    }
}
