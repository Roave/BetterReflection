<?php

namespace Roave\BetterReflectionTest\Fixture;

class Php74PropertyTypeDeclarations
{
    public int $integerProperty = 0;

    public \stdClass $classProperty;

    public $noTypeProperty;

    public ?string $nullableStringProperty;

    public array $arrayProperty = [];
}
