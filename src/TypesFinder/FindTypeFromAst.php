<?php

namespace BetterReflection\TypesFinder;

use PhpParser\Node\Name\FullyQualified;

class FindTypeFromAst
{
    /**
     * Given an AST type, attempt to find a resolved type
     *
     * @todo resolve with context
     * @param $astType
     * @return \phpDocumentor\Reflection\Type|null
     */
    public function __invoke($astType)
    {
        if (is_string($astType)) {
            $typeString = $astType;
        }

        if ($astType instanceof FullyQualified) {
            $typeString = $astType->toString();
        }

        if (!isset($typeString)) {
            return null;
        }

        $types = (new ResolveTypes())->__invoke([$typeString]);

        return reset($types);
    }
}
