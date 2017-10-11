<?php
declare(strict_types=1);

namespace Rector\BetterReflection\TypesFinder;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use PhpParser\Node\Param as ParamNode;
use PhpParser\Node\Stmt\Namespace_;
use Rector\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Rector\BetterReflection\TypesFinder\PhpDocumentor\NamespaceNodeToReflectionTypeContext;

class FindParameterType
{
    /**
     * @var ResolveTypes
     */
    private $resolveTypes;

    /**
     * @var DocBlockFactory
     */
    private $docBlockFactory;

    /**
     * @var NamespaceNodeToReflectionTypeContext
     */
    private $makeContext;

    public function __construct()
    {
        $this->resolveTypes    = new ResolveTypes();
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->makeContext     = new NamespaceNodeToReflectionTypeContext();
    }

    /**
     * Given a function and parameter, attempt to find the type of the parameter.
     *
     * @return Type[]
     */
    public function __invoke(ReflectionFunctionAbstract $function, ?Namespace_ $namespace, ParamNode $node) : array
    {
        $docComment = $function->getDocComment();

        if ('' === $docComment) {
            return [];
        }

        $context = $this->makeContext->__invoke($namespace);

        /** @var \phpDocumentor\Reflection\DocBlock\Tags\Param[] $paramTags */
        $paramTags = $this
            ->docBlockFactory
            ->create($docComment, $context)
            ->getTagsByName('param');

        foreach ($paramTags as $paramTag) {
            if ($paramTag->getVariableName() === $node->name) {
                return $this->resolveTypes->__invoke(\explode('|', (string) $paramTag->getType()), $context);
            }
        }

        return [];
    }
}
