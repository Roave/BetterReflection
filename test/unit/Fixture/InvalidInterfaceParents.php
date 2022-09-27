<?php

namespace Roave\BetterReflectionTest\Fixture\InvalidInterfaceParents;

interface InterfaceExtendsSelf extends InterfaceExtendsSelf {}

interface Interface1 extends Interface2 {}
interface Interface2 extends Interface1 {}
interface Interface3 extends Interface2 {}
