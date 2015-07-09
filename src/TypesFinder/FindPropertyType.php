<?php

namespace BetterReflection\TypesFinder;

use PhpParser\Node\Stmt\Property as PropertyNode;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\ContextFactory;
use BetterReflection\Reflection\ReflectionProperty;

class FindPropertyType
{
    /**
     * Given a property, attempt to find the type of the property
     *
     * @param PropertyNode $node
     * @param ReflectionProperty $reflectionProperty
     * @return Type[]
     */
    public function __invoke(PropertyNode $node, ReflectionProperty $reflectionProperty)
    {
        $contextFactory = new ContextFactory();
        $context = $contextFactory->createForNamespace(
            $reflectionProperty->getDeclaringClass()->getNamespaceName(),
            $reflectionProperty->getDeclaringClass()->getLocatedSource()->getSource()
        );

        /* @var \PhpParser\Comment\Doc $comment */
        if (!$node->hasAttribute('comments')) {
            return [];
        }
        $comment = $node->getAttribute('comments')[0];
        $docBlock = new DocBlock(
            $comment->getReformattedText(),
            new DocBlock\Context(
                $context->getNamespace(),
                $context->getNamespaceAliases()
            )
        );

        /* @var \phpDocumentor\Reflection\DocBlock\Tag\VarTag $varTag */
        $resolvedTypes = [];
        $varTags = $docBlock->getTagsByName('var');
        foreach ($varTags as $varTag) {
            $resolvedTypes = array_merge(
                $resolvedTypes,
                (new ResolveTypes())->__invoke($varTag->getTypes(), $context)
            );
        }
        return $resolvedTypes;
    }
}
