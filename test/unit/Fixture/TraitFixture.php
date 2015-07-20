<?php

// Simple trait usage
trait TraitFixtureTraitA
{
    public function foo() {}
}
class TraitFixtureA
{
    use TraitFixtureTraitA;
}

// No trait usage
class TraitFixtureB
{
}
// Aliasing 1
trait TraitFixtureTraitC
{
    public function a() {}
    public function b() {}
    public function c() {}
}
class TraitFixtureC
{
    use TraitFixtureTraitC {
        a as protected a_protected;
        b as b_renamed;
        c as private;
    }
}

// Conflict resolution
trait TraitFixtureTraitD1
{
    public function foo() {}
}
trait TraitFixtureTraitD2
{
    public function foo() {}
}
class TraitFixtureD
{
    use TraitFixtureTraitD1, TraitFixtureTraitD2 {
        TraitFixtureTraitD1::foo insteadof TraitFixtureTraitD2;
    }
}
