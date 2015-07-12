<?php

namespace BetterReflection\TypesFinder;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\ContextFactory;
use BetterReflection\Reflection\ReflectionProperty;

class FindPropertyType
{
    /**
     * Given a property, attempt to find the type of the property.
     *
     * @param ReflectionProperty $reflectionProperty
     * @return Type[]
     */
    public function __invoke(ReflectionProperty $reflectionProperty)
    {
        $contextFactory = new ContextFactory();
        $context = $contextFactory->createForNamespace(
            $reflectionProperty->getDeclaringClass()->getNamespaceName(),
            $reflectionProperty->getDeclaringClass()->getLocatedSource()->getSource()
        );

        $docBlock = new DocBlock(
            $reflectionProperty->getDocComment(),
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
