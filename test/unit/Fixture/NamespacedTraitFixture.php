<?php

namespace Namespaced;

trait TraitFixtureTraitA
{

}

// Trait in a Class
class ClassFixture
{
    use TraitFixtureTraitA;
}

// Trait in a Trait
trait TraitFixtureTraitB
{
    use TraitFixtureTraitA;
}
