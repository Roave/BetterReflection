<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use PhpParser\NodeAbstract;

/** @internal */
final class GetLastDocComment
{
    public static function forNode(NodeAbstract $node): string
    {
        $docComment = $node->getDocComment();

        return $docComment !== null
            ? (string) $docComment->getReformattedText()
            : '';
    }
}
