<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use PhpParser\Comment\Doc;
use PhpParser\NodeAbstract;
use function assert;
use function is_string;

/**
 * @internal
 */
final class GetFirstDocComment
{
    public static function forNode(NodeAbstract $node) : string
    {
        foreach ($node->getComments() as $comment) {
            if ($comment instanceof Doc) {
                $text = $comment->getReformattedText();

                assert(is_string($text));

                return $text;
            }
        }

        return '';
    }
}
