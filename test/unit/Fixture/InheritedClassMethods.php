<?php

interface Foo {
    public function a();
}

interface Boo extends Foo
{}

trait Bar {
    public function b() {}
}

class Baz implements Foo {
    public function c() {}

    protected function d() {}

    private function e() {}
}

abstract class Qux extends Baz implements Foo, Boo {
    use Bar;
    public function f() {}
}
