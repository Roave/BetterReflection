<?php

namespace Roave\BetterReflectionTest\Fixture;

class AutoloadableByAlias
{
}

class_alias(AutoloadableByAlias::class, 'Roave\BetterReflectionTest\Fixture\AutoloadableAlias');
