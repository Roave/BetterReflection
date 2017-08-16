<?php

interface Foo {
    const A = 'a';
}

interface Boo {
    public const B = 'b';
}

class Baz implements Foo {
    public const C = 'c';

    protected const D = 'd';

    private const E = 'e';
}

abstract class Qux extends Baz implements Boo {
    public const F = 'f';

    protected const D = 'dd';
}

abstract class Next extends Qux {
    public const F = 'ff';
}
