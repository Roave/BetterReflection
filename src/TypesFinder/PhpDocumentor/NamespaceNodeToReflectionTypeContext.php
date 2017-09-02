<?php
declare(strict_types=1);

namespace Roave\BetterReflection\TypesFinder\PhpDocumentor;

use phpDocumentor\Reflection\Types\Context;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;

class NamespaceNodeToReflectionTypeContext
{
    public function __invoke(?Namespace_ $namespace) : Context
    {
        if (! $namespace) {
            return new Context('');
        }

        return new Context(
            $namespace->name ? $namespace->name->toString() : '',
            $this->aliasesToFullyQualifiedNames($this->useStatements($namespace))
        );
    }

    /**
     * @param Use_[] $useStatements
     *
     * @return string[] indexed by alias
     */
    private function aliasesToFullyQualifiedNames(array $useStatements) : array
    {
        return array_merge([], ...array_merge([], ...array_map(function (Use_ $use) : array {
            return array_map(function (UseUse $useUse) : array {
                return [$useUse->alias => $useUse->name->toString()];
            }, $use->uses);
        }, $useStatements)));
    }

    /**
     * @param null|Namespace_ $namespace
     *
     * @return Use_[]
     */
    private function useStatements(Namespace_ $namespace) : array
    {
        return array_filter(
            $namespace->stmts ?? [],
            function (Node $node) : bool {
                return $node instanceof Use_;
            }
        );
    }
}
