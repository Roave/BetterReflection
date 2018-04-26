<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use PhpParser\Node;

class CollectByType
{
    /**
     * Locates {@see Node}s by type.
     *
     * @param CollectByTypeInstructions[] $collectInstructions
     * Defines how {@see Node}s are to be collected and traversed.
     *
     * @param array $nodesList
     * Must be `Iterable<Mixed, Iterable<Mixed, Mixed|Node>>`; `Mixed|Node` means that it
     * will ignore anything that is not `Node`s.
     *
     * @return Node[]
     * Returns the list of collected {@see Node}s.
     */
    function collect(array $collectInstructions, array $nodesList){
        /** @var CollectByTypeInstructions[] $collectInstructions */
        $collect = [];

        TRAVERSE:

        $newNodesList = [];
        foreach($nodesList as $nodes){
            assert(is_array($nodes));

            foreach($nodes as $node){
                if(!$node instanceof Node){ continue; }
                assert($node instanceof Node);

                $subNodeNames = NULL;

                foreach($collectInstructions as $collectInstruction){
                    if($node instanceof $collectInstruction->ifType){
                        if($collectInstruction->collectSelf){
                            $collect[] = $node;
                        }
                        $subNodeNames = $collectInstruction->alsoSearchInSubNodesNames;
                        break;
                    }
                }

                $subNodeNames = $subNodeNames ?? $node->getSubNodeNames();

                foreach($subNodeNames as $subNodeName){
                    $subNodes = $node->{$subNodeName};
                    $newNodesList[] = is_array($subNodes) ? $subNodes : [$subNodes];
                }
            }
        }

        if($newNodesList === []){
            return $collect;
        }else{
            $nodesList = $newNodesList;
            goto TRAVERSE;
        }
    }
}
