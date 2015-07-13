<?php

trait TraitFixtureTraitA
{
    public function foo() {}
}

trait TraitFixtureTraitB
{
    public function foo() {}
}

class TraitFixtureA
{
    use TraitFixtureTraitA;
}

class TraitFixtureB
{
}
