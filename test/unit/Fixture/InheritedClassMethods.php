<?php

interface Foo {
    public function a();
}

interface Boo extends Foo
{
    public function f();

    public function g();
}

trait Bar {
    abstract function h();

    public function b() {}

    public function k() {}
}

trait Bar2 {
    abstract public function h();
}

trait Bar3 {
    use Bar {
        k as j;
    }

    public function k() {}
}


abstract class Baz implements Foo {
    public function c() {}

    protected function d() {}

    private function e() {}
}

abstract class Qux extends Baz implements Foo, Boo {
    use Bar;
    use Bar2 {
        Bar2::h as i;
    }
    use Bar3;

    public function f() {}
}
