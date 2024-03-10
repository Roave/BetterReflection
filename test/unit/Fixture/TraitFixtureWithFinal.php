<?php

trait SimpleTrait
{
    protected function foo() {}
    final protected function boo() {}
}

class TraitFixtureWithFinal
{
    use SimpleTrait {
        foo as final;
    }
}

class TraitFixtureWithPublic
{
    use SimpleTrait {
        boo as public;
    }
}
