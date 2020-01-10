<?php

declare(strict_types=1);

namespace Roave\BetterReflection\TypesFinder;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Context;
use function assert;

class ResolveTypes
{
    /** @var TypeResolver */
    private $typeResolver;

    public function __construct()
    {
        $this->typeResolver = new TypeResolver();
    }

    /**
     * @param string[] $stringTypes
     *
     * @return Type[]
     */
    public function __invoke(array $stringTypes, Context $context) : array
    {
        $resolvedTypes = [];

        foreach ($stringTypes as $stringType) {
            $resolvedType = $this->typeResolver->resolve($stringType, $context);
            assert($resolvedType instanceof Type);
            $resolvedTypes[] = $resolvedType;
        }

        return $resolvedTypes;
    }
}
