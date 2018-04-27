<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use PhpParser\Node;

class CollectByTypeInstructions
{
    /** @var string */
    public $ifType;

    /** @var bool */
    public $collectSelf;

    /** @var array|string[] */
    public $alsoSearchInSubNodesNames;

    /**
     * @param string              $ifType
     * Matches a {@see Node} of `$ifType` type.
     *
     * @param bool                $collectSelf
     * Whether the {@see Node} being an instance of `$ifType` must be collected.
     *
     * @param array|string[]|null $alsoSearchInSubNodesNames
     * Defines the subnodes of `$ifType` in which the traversal algorithm must continue
     * searching for more {@see Node}s. If `null`, the algorithm will look in all
     * `$ifType`'s children ({@see Node::getSubNodeNames()}); if empty array it will not
     * traverse its children. Otherwise, if an array of strings, the algorithm will traverse
     * each node in `$node->{$string}`.
     */
    public function __construct(
        string $ifType,
        bool $collectSelf,
        ?array $alsoSearchInSubNodesNames
    ) {
        $this->ifType                    = $ifType;
        $this->collectSelf               = $collectSelf;
        $this->alsoSearchInSubNodesNames = $alsoSearchInSubNodesNames;
    }
}
