<?php

namespace Roave\BetterReflectionTest\Fixture;

use AllowDynamicProperties;

trait DefaultPropertiesTrait
{
    public $fromTrait = 'anything';
}

#[AllowDynamicProperties]
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
