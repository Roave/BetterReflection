<?php

declare(strict_types=1);

namespace Roave\BetterReflection\TypesFinder;

use LogicException;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use PhpParser\Node\Expr\Error;
use PhpParser\Node\Param as ParamNode;
use PhpParser\Node\Stmt\Namespace_;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\TypesFinder\PhpDocumentor\NamespaceNodeToReflectionTypeContext;
use function explode;

class FindParameterType
{
    /** @var ResolveTypes */
    private $resolveTypes;

    /** @var DocBlockFactory */
    private $docBlockFactory;

    /** @var NamespaceNodeToReflectionTypeContext */
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

        if ($docComment === '') {
            return [];
        }

        $context = $this->makeContext->__invoke($namespace);

        /** @var Param[] $paramTags */
        $paramTags = $this
            ->docBlockFactory
            ->create($docComment, $context)
            ->getTagsByName('param');

        foreach ($paramTags as $paramTag) {
            if ($node->var instanceof Error) {
                throw new LogicException('PhpParser left an "Error" node in the parameters AST, this should NOT happen');
            }

            if ($paramTag->getVariableName() === $node->var->name) {
                return $this->resolveTypes->__invoke(explode('|', (string) $paramTag->getType()), $context);
            }
        }

        return [];
    }
}
