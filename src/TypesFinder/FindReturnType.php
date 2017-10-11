<?php
declare(strict_types=1);

namespace Rector\BetterReflection\TypesFinder;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use PhpParser\Node\Stmt\Namespace_;
use Rector\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Rector\BetterReflection\TypesFinder\PhpDocumentor\NamespaceNodeToReflectionTypeContext;

class FindReturnType
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
     * Given a function, attempt to find the return type.
     *
     * @return Type[]
     */
    public function __invoke(ReflectionFunctionAbstract $function, ?Namespace_ $namespace) : array
    {
        $docComment = $function->getDocComment();

        if ('' === $docComment) {
            return [];
        }

        $context = $this->makeContext->__invoke($namespace);

        /** @var \phpDocumentor\Reflection\DocBlock\Tags\Return_[] $returnTags */
        $returnTags = $this
            ->docBlockFactory
            ->create($docComment, $context)
            ->getTagsByName('return');

        foreach ($returnTags as $returnTag) {
            return $this->resolveTypes->__invoke(\explode('|', (string) $returnTag->getType()), $context);
        }

        return [];
    }
}
