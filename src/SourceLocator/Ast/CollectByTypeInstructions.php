<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

class CollectByTypeInstructions
{
    public $ifType;
    public $collectSelf;
    public $alsoSearchInSubNodesNames;

    function __construct(string $ifType, bool $collectSelf, ?array $alsoSearchInSubNodesNames){
        $this->ifType = $ifType;
        $this->collectSelf = $collectSelf;
        $this->alsoSearchInSubNodesNames = $alsoSearchInSubNodesNames;
    }
}
