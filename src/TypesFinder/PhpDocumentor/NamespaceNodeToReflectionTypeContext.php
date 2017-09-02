<?php
declare(strict_types=1);

namespace Roave\BetterReflection\TypesFinder\PhpDocumentor;

use phpDocumentor\Reflection\Types\Context;
use PhpParser\Node;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;

class NamespaceNodeToReflectionTypeContext
{
    public function __invoke(?Namespace_ $namespace) : Context
    {
        if ( ! $namespace) {
            return new Context('');
        }

        return new Context(
            $namespace->name ? $namespace->name->toString() : '',
            $this->aliasesToFullyQualifiedNames($namespace)
        );
    }

    /**
     * @return string[] indexed by alias
     */
    private function aliasesToFullyQualifiedNames(Namespace_ $namespace) : array
    {
        $aliases = [];

        // Note: we replaced an `array_filter` with a `foreach` because this seems to be very performance-sensitive API
        foreach ($this->classAlikeUses($namespace) as $use) {
            foreach ($use->uses as $useUse) {
                if ($use instanceof GroupUse) {
                    $aliases[$useUse->alias] = $use->prefix->toString() . '\\' . $useUse->name->toString();

                    continue;
                }

                $aliases[$useUse->alias] = $useUse->name->toString();
            }
        }

        return $aliases;
    }

    /**
     * @return Use_[]|GroupUse[]
     */
    private function classAlikeUses(Namespace_ $namespace) : array
    {
        $validNodes = [];

        // Note: we replaced an `array_filter` with a `foreach` because this seems to be very performance-sensitive API
        foreach ($namespace->stmts ?? [] as $node) {
            if (
                ($node instanceof Use_ || $node instanceof GroupUse)
                && ($node->type === Use_::TYPE_UNKNOWN || $node->type === Use_::TYPE_NORMAL)
            ) {
                $validNodes[] = $node;
            }
        }

        return $validNodes;
    }
}
