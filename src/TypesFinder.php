<?php

namespace Asgrim;

use PhpParser\Node\Stmt\Property as PropertyNode;
use phpDocumentor\Reflection\DocBlock;

class TypesFinder
{
    public static function findTypeForProperty(PropertyNode $node)
    {
        /* @var \PhpParser\Comment\Doc $comment */
        if (!$node->hasAttribute('comments')) {
            return [];
        }
        $comment = $node->getAttribute('comments')[0];
        $docBlock = new DocBlock($comment->getReformattedText());

        /* @var \phpDocumentor\Reflection\DocBlock\Tag\VarTag $varTag */
        $varTag = $docBlock->getTagsByName('var')[0];
        return $varTag->getTypes();
    }
}
