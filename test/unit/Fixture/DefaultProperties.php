<?php

namespace Roave\BetterReflectionTest\Fixture;

trait DefaultPropertiesTrait
{
    public $fromTrait = 'anything';
}

class DefaultProperties
{
    use DefaultPropertiesTrait;

    const SOME_CONST = 'const';

    public $hasDefault = self::SOME_CONST;
    public $hasNullAsDefault = null;
    public $noDefault;

    public int $hasDefaultWithType = 123;
    public ?string $hasNullAsDefaultWithType = null;
    public string $noDefaultWithType;
}
