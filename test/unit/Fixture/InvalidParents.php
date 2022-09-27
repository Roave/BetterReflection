<?php

namespace Roave\BetterReflectionTest\Fixture\InvalidParents;

class ClassExtendsSelf extends ClassExtendsSelf {}

class Class1 extends Class2 {}
class Class2 extends Class1 {}
class Class3 extends Class2 {}
