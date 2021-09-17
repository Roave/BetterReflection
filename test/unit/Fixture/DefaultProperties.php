<?php

class Foo
{
    public $hasDefault = 123;
    public $hasNullAsDefault = null;
    public $noDefault;

    public int $hasDefaultWithType = 123;
    public ?string $hasNullAsDefaultWithType = null;
    public string $noDefaultWithType;
}
