<?php
declare(strict_types=1);

namespace Roave\BetterReflection\TypesFinder;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;

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
     * @var ContextFactory
     */
    private $contextFactory;

    public function __construct()
    {
        $this->resolveTypes    = new ResolveTypes();
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->contextFactory  = new ContextFactory();
    }

    /**
     * Given a function, attempt to find the return type.
     *
     * @param ReflectionFunctionAbstract $function
     * @param Use_[] $useStatements
     * @return Type[]
     */
    public function __invoke(ReflectionFunctionAbstract $function, ?Namespace_ $namespace) : array
    {
        $docComment = $function->getDocComment();

        if ('' === $docComment) {
            return [];
        }

        $context = $this->createContextForFunction($function, $namespace, $this->useStatements($namespace));

        /** @var \phpDocumentor\Reflection\DocBlock\Tags\Return_[] $returnTags */
        $returnTags = $this->docBlockFactory->create(
            $docComment,
            new Context(
                $context->getNamespace(),
                $context->getNamespaceAliases()
            )
        )->getTagsByName('return');

        foreach ($returnTags as $returnTag) {
            return $this->resolveTypes->__invoke(\explode('|', (string) $returnTag->getType()), $context);
        }

        return [];
    }

    /**
     * @param null|Namespace_ $namespace
     *
     * @return Use_[]
     */
    private function useStatements(?Namespace_ $namespace) : array
    {
        if (null === $namespace) {
            return [];
        }

        return array_filter(
            $namespace->stmts ?? [],
            function (Node $node) : bool {
                return $node instanceof Use_;
            }
        );
    }

    /**
     * @param ReflectionFunctionAbstract $function
     * @param Namespace_|null $namespace
     * @param Use_[] $useStatements
     *
     * @return Context
     */
    private function createContextForFunction(ReflectionFunctionAbstract $function, ?Namespace_ $namespace, array $useStatements) : Context
    {

        $uses = array_merge([], ...array_merge([], ...array_map(function (Use_ $use) : array {
            return array_map(function (UseUse $useUse) : array {
                return [$useUse->alias => $useUse->name->toString()];
            }, $use->uses);
        }, $useStatements)));

        return new Context(
            ($namespace && $namespace->name) ? $namespace->name->toString() : '',
            $uses
        );

        if ($function instanceof ReflectionMethod) {
            $declaringClass = $function->getDeclaringClass();

            return new Context(
                ($namespace && $namespace->name) ? $namespace->name->toString() : '',
                $uses
            );
            return $this->contextFactory->createForNamespace(
                $declaringClass->getNamespaceName(),
                $declaringClass->getLocatedSource()->getSource()
            );
        }

        return $this->contextFactory->createForNamespace(
            $function->getNamespaceName(),
            $function->getLocatedSource()->getSource()
        );
    }
}
