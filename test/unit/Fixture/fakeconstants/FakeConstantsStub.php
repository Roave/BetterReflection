<?php

namespace Roave\BetterReflectionTest\Fixture;

define('DEFINE_CONSTANT', 'obsoleteValue');
const CONST_CONSTANT = 'obsoleteValue';

class FakeConstants
{
    public const CLASS_CONSTANT = 'obsoleteValue';
}
