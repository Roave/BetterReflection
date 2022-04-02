<?php

interface Foo {
    const A = 'a';
}

interface Boo {
    public const B = 'b';
}

interface Coo {
    public const B = 'wrong-b';
}

class Baz implements Foo {
    public const C = 'c';

    protected const D = 'd';

    private const E = 'e';
}

abstract class Qux extends Baz implements Boo, Coo {
    public const F = 'f';

    private const E = 'ee';

    protected const D = 'dd';
}

abstract class Next extends Qux {
    private $stmtThatIsNotConstant;

    public const F = 'ff';

    public function stmtThatIsNotConstant()
    {
    }
}
