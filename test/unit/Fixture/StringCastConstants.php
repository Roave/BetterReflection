<?php

namespace Rector\BetterReflectionTest\Fixture;

class StringCastConstants
{
    public const PUBLIC_CONSTANT = true;
    protected const PROTECTED_CONSTANT = 0;
    private const PRIVATE_CONSTANT = 'string';
    const NO_VISIBILITY_CONSTANT = [];
}
