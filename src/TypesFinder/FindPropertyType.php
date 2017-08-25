<?php
declare(strict_types=1);

namespace Roave\BetterReflection\TypesFinder;

use phpDocumentor\Reflection\DocBlock\Tags\Var_;
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
    public function __invoke(ReflectionProperty $reflectionProperty) : array
    {
        $docComment = $reflectionProperty->getDocComment();

        if ('' === $docComment) {
            return [];
        }

        $contextFactory = new ContextFactory();
        $context        = $contextFactory->createForNamespace(
            $reflectionProperty->getDeclaringClass()->getNamespaceName(),
            $reflectionProperty->getDeclaringClass()->getLocatedSource()->getSource()
        );

        $docBlock = DocBlockFactory::createInstance()->create(
            $docComment,
            new Context(
                $context->getNamespace(),
                $context->getNamespaceAliases()
            )
        );

        /* @var \phpDocumentor\Reflection\DocBlock\Tags\Var_[] $varTags */
        $varTags      = $docBlock->getTagsByName('var');
        $typeResolver = new ResolveTypes();

        return \array_merge(
            [],
            ...\array_map(function (Var_ $varTag) use ($typeResolver, $context) {
                return $typeResolver->__invoke(\explode('|', (string) $varTag->getType()), $context);
            }, $varTags)
        );
    }
}
