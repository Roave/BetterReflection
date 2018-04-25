<?php

declare(strict_types=1);

namespace Roave\BetterReflection\TypesFinder;

use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use PhpParser\Node\Stmt\Namespace_;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\TypesFinder\PhpDocumentor\NamespaceNodeToReflectionTypeContext;
use function array_map;
use function array_merge;
use function explode;

class FindPropertyType
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
     * Given a property, attempt to find the type of the property.
     *
     * @return Type[]
     */
    public function __invoke(ReflectionProperty $reflectionProperty, ?Namespace_ $namespace) : array
    {
        $docComment = $reflectionProperty->getDocComment();

        if ($docComment === '') {
            return [];
        }

        $context = $this->makeContext->__invoke($namespace);
        /** @var Var_[] $varTags */
        $varTags = $this->docBlockFactory->create($docComment, $context)->getTagsByName('var');

        return array_merge(
            [],
            ...array_map(function (Var_ $varTag) use ($context) {
                return $this->resolveTypes->__invoke(explode('|', (string) $varTag->getType()), $context);
            }, $varTags)
        );
    }
}
