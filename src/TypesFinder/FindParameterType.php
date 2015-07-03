<?php

namespace BetterReflection\TypesFinder;

use phpDocumentor\Reflection\Types\Context;
use PhpParser\Node\Param as ParamNode;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use BetterReflection\Reflection\ReflectionFunctionAbstract;

class FindParameterType
{
    /**
     * Given a function and parameter, attempt to find the type of the parameter
     *
     * @todo needs some context going on here...
     * @param ReflectionFunctionAbstract $function
     * @param ParamNode $node
     * @return Type[]
     */
    public function __invoke(ReflectionFunctionAbstract $function, ParamNode $node)
    {
        $docBlock = new DocBlock($function->getDocComment());

        $paramTags = $docBlock->getTagsByName('param');

        foreach ($paramTags as $paramTag) {
            /* @var $paramTag \phpDocumentor\Reflection\DocBlock\Tag\ParamTag */
            if ($paramTag->getVariableName() === '$' . $node->name) {
                return (new ResolveTypes())->__invoke($paramTag->getTypes(), new Context(''));
            }
        }
        return [];
    }
}
