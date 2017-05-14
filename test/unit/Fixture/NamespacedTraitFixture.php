<?php

namespace Namespaced
{
    trait TraitFixtureTraitA {

    }

    class ClassFixture {
        use TraitFixtureTraitA;
    }

    trait TraitFixtureTraitB
    {
        use TraitFixtureTraitA;
    }
}
