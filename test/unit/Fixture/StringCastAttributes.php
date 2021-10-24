<?php

namespace Roave\BetterReflectionTest\Fixture;

#[NoArguments]
#[WithArguments('arg1', 'very long string that will be truncated', arg3: [1, 2, 3], arg4: true)]
class ClassWithAttributesForStringCast
{
}
