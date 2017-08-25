<?php
declare(strict_types=1);

namespace Roave\BetterReflection\TypesFinder;

use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\TypeResolver;

class ResolveTypes
{
    /**
     * @param string[] $stringTypes
     * @param Context $context
     * @return \phpDocumentor\Reflection\Type[]
     */
    public function __invoke(array $stringTypes, Context $context) : array
    {
        $resolvedTypes = [];
        $resolver      = new TypeResolver();

        foreach ($stringTypes as $stringType) {
            $resolvedTypes[] = $resolver->resolve($stringType, $context);
        }

        return $resolvedTypes;
    }
}
