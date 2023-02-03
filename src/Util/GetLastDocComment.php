<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use PhpParser\NodeAbstract;

use function assert;
use function is_string;

/** @internal */
final class GetLastDocComment
{
    /**
     * @return non-empty-string|null
     *
     * @psalm-pure
     */
    public static function forNode(NodeAbstract $node): string|null
    {
        /** @psalm-suppress ImpureMethodCall */
        $docComment = $node->getDocComment();

        if ($docComment === null) {
            return null;
        }

        /** @psalm-suppress ImpureMethodCall */
        $comment = $docComment->getReformattedText();
        assert(is_string($comment) && $comment !== '');

        return $comment;
    }
}
