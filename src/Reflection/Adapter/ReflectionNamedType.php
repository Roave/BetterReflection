<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;

class ReflectionNamedType extends ReflectionType
{
    /** @var BetterReflectionType */
    private $betterReflectionType;

    public function __construct(BetterReflectionType $betterReflectionType)
    {
        parent::__construct($betterReflectionType);

        $this->betterReflectionType = $betterReflectionType;
    }

    public function getName()
    {
        return $this->betterReflectionType->getName();
    }
}
