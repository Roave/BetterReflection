<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Annotation;

use phpDocumentor\Reflection\DocBlockFactory;

/**
 * @internal
 */
final class AnnotationHelper
{
    public const TENTATIVE_RETURN_TYPE_ANNOTATION = 'betterReflectionTentativeReturnType';

    private static ?DocBlockFactory $docBlockFactory = null;

    /**
     * @psalm-pure
     */
    public static function isDeprecated(string $docComment): bool
    {
        if ($docComment === '') {
            return false;
        }

        /** @psalm-suppress ImpureMethodCall */
        return self::getDocBlockFactory()->create($docComment)->hasTag('deprecated');
    }

    /**
     * @psalm-pure
     */
    public static function hasTentativeReturnType(string $docComment): bool
    {
        if ($docComment === '') {
            return false;
        }

        /** @psalm-suppress ImpureMethodCall */
        return self::getDocBlockFactory()->create($docComment)->hasTag(self::TENTATIVE_RETURN_TYPE_ANNOTATION);
    }

    private static function getDocBlockFactory(): DocBlockFactory
    {
        self::$docBlockFactory ??= DocBlockFactory::createInstance();

        return self::$docBlockFactory;
    }
}
