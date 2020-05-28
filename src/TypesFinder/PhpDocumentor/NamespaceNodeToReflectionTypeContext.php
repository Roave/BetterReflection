<?php

declare(strict_types=1);

namespace Roave\BetterReflection\TypesFinder\PhpDocumentor;

use phpDocumentor\Reflection\Types\Context;
use PhpParser\Node;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use function array_filter;
use function array_map;
use function array_merge;
use function in_array;

class NamespaceNodeToReflectionTypeContext
{
    public function __invoke(?Namespace_ $namespace) : Context
    {
        if (! $namespace) {
            return new Context('');
        }

        return new Context(
            $namespace->name ? $namespace->name->toString() : '',
            $this->aliasesToFullyQualifiedNames($namespace),
        );
    }

    /**
     * @return string[] indexed by alias
     */
    private function aliasesToFullyQualifiedNames(Namespace_ $namespace) : array
    {
        // flatten(flatten(map(stuff)))
        return array_merge(
            [],
            ...array_merge(
                [],
                ...array_map(
                    /** @param Use_|GroupUse $use */
                    static function ($use) : array {
                        return array_map(
                            static function (UseUse $useUse) use ($use) : array {
                                if ($use instanceof GroupUse) {
                                    return [$useUse->getAlias()->toString() => $use->prefix->toString() . '\\' . $useUse->name->toString()];
                                }

                                return [$useUse->getAlias()->toString() => $useUse->name->toString()];
                            },
                            $use->uses,
                        );
                    },
                    $this->classAlikeUses($namespace),
                ),
            ),
        );
    }

    /**
     * @return Use_[]|GroupUse[]
     */
    private function classAlikeUses(Namespace_ $namespace) : array
    {
        return array_filter(
            $namespace->stmts,
            static function (Node $node) : bool {
                return (
                    $node instanceof Use_
                    || $node instanceof GroupUse
                ) && in_array($node->type, [Use_::TYPE_UNKNOWN, Use_::TYPE_NORMAL], true);
            },
        );
    }
}
