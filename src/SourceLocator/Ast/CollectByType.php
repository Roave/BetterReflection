<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use PhpParser\Node;
use function assert;
use function is_array;

class CollectByType
{
    /**
     * Locates {@see Node}s by type.
     *
     * @param CollectByTypeInstructions[] $collectInstructions
     * Defines how {@see Node}s are to be collected and traversed.
     *
     * @param Node[][]|mixed              $nodesList
     * Must be `Iterable<Mixed, Iterable<Mixed, Mixed|Node>>`; `Mixed|Node` means that it
     * will ignore anything that is not `Node`s.
     *
     * @return Node[]
     * Returns the list of collected {@see Node}s.
     */
    public function collect(array $collectInstructions, array $nodesList) : array
    {
        $collect = [];

        // PHPCS thinks it's a constant but it's actually a goto label
        // @codingStandardsIgnoreStart
        TRAVERSE:
        // @codingStandardsIgnoreEnd

        $newNodesList = [];
        foreach ($nodesList as $nodes) {
            assert(is_array($nodes));

            foreach ($nodes as $node) {
                if (! $node instanceof Node) {
                    continue;
                }

                $subNodeNames = null;

                foreach ($collectInstructions as $collectInstruction) {
                    if ($node instanceof $collectInstruction->ifType) {
                        if ($collectInstruction->collectSelf) {
                            $collect[] = $node;
                        }
                        $subNodeNames = $collectInstruction->alsoSearchInSubNodesNames;
                        break;
                    }
                }

                $subNodeNames = $subNodeNames ?? $node->getSubNodeNames();

                foreach ($subNodeNames as $subNodeName) {
                    $subNodes       = $node->{$subNodeName};
                    $newNodesList[] = is_array($subNodes) ? $subNodes : [$subNodes];
                }
            }
        }

        if ($newNodesList === []) {
            return $collect;
        }

        $nodesList = $newNodesList;
        goto TRAVERSE;
    }
}
