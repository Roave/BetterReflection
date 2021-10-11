<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Deprecated;

use phpDocumentor\Reflection\DocBlockFactory;

/**
 * @internal
 */
final class DeprecatedHelper
{
    private static ?DocBlockFactory $docBlockFactory = null;

    public static function isDeprecated(string $docComment): bool
    {
        if ($docComment === '') {
            return false;
        }

        self::$docBlockFactory ??= DocBlockFactory::createInstance();

        $docBlock = self::$docBlockFactory->create($docComment);

        return $docBlock->hasTag('deprecated');
    }
}
