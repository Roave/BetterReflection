<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Annotation;

use function preg_match;

/** @internal */
final class AnnotationHelper
{
    public const TENTATIVE_RETURN_TYPE_ANNOTATION = 'betterReflectionTentativeReturnType';

    /** @psalm-pure */
    public static function isDeprecated(string $docComment): bool
    {
        if ($docComment === '') {
            return false;
        }

        return preg_match('~\*\s*@deprecated(?=\s|\*)~', $docComment) === 1;
    }

    /** @psalm-pure */
    public static function hasTentativeReturnType(string $docComment): bool
    {
        if ($docComment === '') {
            return false;
        }

        return preg_match('~\*\s*@' . self::TENTATIVE_RETURN_TYPE_ANNOTATION . '(?=\s|\*)~', $docComment) === 1;
    }
}
