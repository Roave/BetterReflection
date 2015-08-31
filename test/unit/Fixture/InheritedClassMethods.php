<?php

interface Foo {
    public function a();
}

trait Bar {
    public function b() {}
}

class Baz {
    public function c() {}

    protected function d() {}

    private function e() {}
}

abstract class Qux extends Baz implements Foo {
    use Bar;
    public function f() {}
}
