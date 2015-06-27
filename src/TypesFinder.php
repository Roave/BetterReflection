<?php

namespace Asgrim;

use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\Node\Param as ParamNode;
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

    public static function findTypeForParameter(ReflectionFunctionAbstract $function, ParamNode $node)
    {
        $docBlock = new DocBlock($function->getDocComment());

        $paramTags = $docBlock->getTagsByName('param');

        foreach ($paramTags as $paramTag) {
            /* @var $paramTag \phpDocumentor\Reflection\DocBlock\Tag\ParamTag */
            if ($paramTag->getVariableName() == '$' . $node->name) {
                return $paramTag->getTypes();
            }
        }
        return [];
    }
}
