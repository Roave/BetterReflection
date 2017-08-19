<?php
declare(strict_types=1);

namespace Roave\BetterReflection\TypesFinder;

use Roave\BetterReflection\Reflection\ReflectionMethod;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use PhpParser\Node\Param as ParamNode;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;

class FindParameterType
{
    /**
     * Given a function and parameter, attempt to find the type of the parameter.
     *
     * @param ReflectionFunctionAbstract $function
     * @param ParamNode $node
     * @return Type[]
     */
    public function __invoke(ReflectionFunctionAbstract $function, ParamNode $node) : array
    {
        $docComment = $function->getDocComment();

        if ('' === $docComment) {
            return [];
        }

        $context = $this->createContextForFunction($function);

        $docBlock = DocBlockFactory::createInstance()->create(
            $docComment,
            new Context(
                $context->getNamespace(),
                $context->getNamespaceAliases()
            )
        );

        $paramTags = $docBlock->getTagsByName('param');

        foreach ($paramTags as $paramTag) {
            /* @var $paramTag \phpDocumentor\Reflection\DocBlock\Tags\Param */
            if ($paramTag->getVariableName() === $node->name) {
                return (new ResolveTypes())->__invoke(explode('|', (string) $paramTag->getType()), $context);
            }
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
