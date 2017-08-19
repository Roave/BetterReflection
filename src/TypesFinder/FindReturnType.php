<?php
declare(strict_types=1);

namespace Roave\BetterReflection\TypesFinder;

use Roave\BetterReflection\Reflection\ReflectionMethod;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
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
    public function __invoke(ReflectionFunctionAbstract $function) : array
    {
        $docComment = $function->getDocComment();

        if ('' === $docComment) {
            return [];
        }

        $context = $this->createContextForFunction($function);

        $returnTags = DocBlockFactory::createInstance()->create(
            $docComment,
            new Context(
                $context->getNamespace(),
                $context->getNamespaceAliases()
            )
        )->getTagsByName('return');

        foreach ($returnTags as $returnTag) {
            /* @var $returnTag \phpDocumentor\Reflection\DocBlock\Tags\Return_ */
            return (new ResolveTypes())->__invoke(\explode('|', (string) $returnTag->getType()), $context);
        }
        return [];
    }

    /**
     * @param ReflectionFunctionAbstract $function
     * @return Context
     */
    private function createContextForFunction(ReflectionFunctionAbstract $function) : Context
    {
        if ($function instanceof ReflectionMethod) {
            $declaringClass = $function->getDeclaringClass();

            return (new ContextFactory())->createForNamespace(
                $declaringClass->getNamespaceName(),
                $declaringClass->getLocatedSource()->getSource()
            );
        }

        return (new ContextFactory())->createForNamespace(
            $function->getNamespaceName(),
            $function->getLocatedSource()->getSource()
        );
    }
}
