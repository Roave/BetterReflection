<?php

namespace Roave\BetterReflection\TypesFinder;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use Roave\BetterReflection\Reflection\ReflectionProperty;

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

        $docBlock = DocBlockFactory::createInstance()->create(
            $reflectionProperty->getDocComment(),
            new Context(
                $context->getNamespace(),
                $context->getNamespaceAliases()
            )
        );

        /* @var \phpDocumentor\Reflection\DocBlock\Tags\Var_ $varTag */
        $resolvedTypes = [];
        $varTags = $docBlock->getTagsByName('var');
        foreach ($varTags as $varTag) {
            $resolvedTypes = array_merge(
                $resolvedTypes,
                (new ResolveTypes())->__invoke(explode('|', $varTag->getType()), $context)
            );
        }
        return $resolvedTypes;
    }
}
