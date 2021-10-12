<?php

trait FooTrait
{
    public $fromTrait = self::SOME_CONST;
}

class Foo
{
    use FooTrait;

    const SOME_CONST = 'const';

    public $hasDefault = 123;
    public $hasNullAsDefault = null;
    public $noDefault;

    public int $hasDefaultWithType = 123;
    public ?string $hasNullAsDefaultWithType = null;
    public string $noDefaultWithType;
}
