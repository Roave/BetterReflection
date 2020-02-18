<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use PhpParser\Comment\Doc;
use PhpParser\NodeAbstract;
use Webmozart\Assert\Assert;

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
                Assert::string($text);

                return $text;
            }
        }

        return '';
    }
}
