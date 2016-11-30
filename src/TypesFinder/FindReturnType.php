<?php

namespace Roave\BetterReflection\TypesFinder;

use Roave\BetterReflection\Reflection\ReflectionMethod;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;

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

        $returnTags = DocBlockFactory::createInstance()->create(
            $function->getDocComment(),
            new Context(
                $context->getNamespace(),
                $context->getNamespaceAliases()
            )
        )->getTagsByName('return');

        foreach ($returnTags as $returnTag) {
            /* @var $returnTag \phpDocumentor\Reflection\DocBlock\Tags\Return_ */
            return (new ResolveTypes())->__invoke(explode('|', $returnTag->getType()), $context);
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
