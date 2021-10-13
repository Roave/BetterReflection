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

    /**
     * @psalm-pure
     */
    public static function isDeprecated(string $docComment): bool
    {
        if ($docComment === '') {
            return false;
        }

        /** @psalm-suppress ImpureStaticProperty, ImpureMethodCall */
        self::$docBlockFactory ??= DocBlockFactory::createInstance();

        /** @psalm-suppress ImpureStaticProperty, ImpureMethodCall */
        return self::$docBlockFactory->create($docComment)->hasTag('deprecated');
    }
}
