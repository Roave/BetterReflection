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

trait TraitFixtureTraitAA
{
    use TraitFixtureTraitA;
}

class TraitFixtureAA
{
    use TraitFixtureTraitAA;
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
trait TraitFixtureTraitC2
{
    public function d() {}
}
trait TraitFixtureTraitC3
{
    use TraitFixtureTraitC2;
}
class TraitFixtureC
{
    use TraitFixtureTraitC {
        a as protected a_protected;
        b as b_renamed;
        c as private;
    }
    use TraitFixtureTraitC3 {
        d as d_renamed;
    }
}

// Conflict resolution
trait TraitFixtureTraitD1
{
    public function foo() {}
    public function boo() {}
    public function hoo() {}
}
trait TraitFixtureTraitD2
{
    public function foo() {}
}
class TraitFixtureD
{
    use TraitFixtureTraitD2, TraitFixtureTraitD1 {
        TraitFixtureTraitD1::foo insteadof TraitFixtureTraitD2;
        TraitFixtureTraitD1::hoo as hooFirstAlias;
        TraitFixtureTraitD1::hoo as hooSecondAlias;
    }

    public function boo() {}
}

trait FirstTraitForFixtureE
{
    public function foo(): void
    {
    }
}

trait SecondTraitForFixtureE
{
    use FirstTraitForFixtureE {
        foo as parentFoo;
    }

    public function foo(): void
    {
    }
}

class TraitFixtureE
{
    use SecondTraitForFixtureE;
}

trait FirstTraitForFixtureF
{

    public function a()
    {
    }

    public function b()
    {
    }

}

trait SecondTraitForFixtureF
{

    public function a()
    {
    }

    public function b()
    {
    }

}

class TraitFixtureF
{

    use FirstTraitForFixtureF, SecondTraitForFixtureF {
        FirstTraitForFixtureF::a insteadof SecondTraitForFixtureF;
        SecondTraitForFixtureF::b insteadof FirstTraitForFixtureF;
        SecondTraitForFixtureF::a as aliasedA;
        FirstTraitForFixtureF::b as aliasedB;
    }

}



trait Trait1FixtureG {
    public function method1() {}
    public function method2() {}
}

trait Trait2FixtureG {
    public function method3() {}
    public function method4() {}
}

class ClassFixtureG {
    use Trait1FixtureG, Trait2FixtureG {
        method1 as alias1;
        method3 as alias3;
    }
}
