<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use PhpParser\Comment\Doc;
use PhpParser\NodeAbstract;

/**
 * @internal
 */
final class GetFirstDocComment
{
    public static function forNode(NodeAbstract $node) : string
    {
        $comments = $node->getAttributes()['comments'] ?? [];
        foreach ($comments as $comment) {
            if ($comment instanceof Doc) {
                return $comment->getReformattedText();
            }
        }

        return '';
    }
}
