<?php

namespace BetterReflection\TypesFinder;

use BetterReflection\Reflection\ReflectionMethod;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use BetterReflection\Reflection\ReflectionFunctionAbstract;

class FindReturnType
{
    /**
     * Given a function, attempt to find the return type.
     *
     * @param ReflectionFunctionAbstract $function
     * @return Type[]
     */
    public function __invoke(ReflectionFunctionAbstract $function)
    {
        $context = $this->createContextForFunction($function);

        $docBlock = new DocBlock(
            $function->getDocComment(),
            new DocBlock\Context(
                $context->getNamespace(),
                $context->getNamespaceAliases()
            )
        );

        $returnTags = $docBlock->getTagsByName('return');

        foreach ($returnTags as $returnTag) {
            /* @var $returnTag \phpDocumentor\Reflection\DocBlock\Tag\ReturnTag */
            return (new ResolveTypes())->__invoke($returnTag->getTypes(), $context);
        }
        return [];
    }

    /**
     * @param ReflectionFunctionAbstract $function
     * @return Context
     */
    private function createContextForFunction(ReflectionFunctionAbstract $function)
    {
        if ($function instanceof ReflectionMethod) {
            $function = $function->getDeclaringClass();
        }

        return (new ContextFactory())->createForNamespace(
            $function->getNamespaceName(),
            $function->getLocatedSource()->getSource()
        );
    }
}
