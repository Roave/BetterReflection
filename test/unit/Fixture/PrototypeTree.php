<?php

interface FooInterface {
    public function foo($a, $b);
}

abstract class ClassA implements FooInterface {
    abstract public function foo($a, $b);
}

class ClassB extends ClassA {
    public function foo($a, $b = 123) {}
}

class ClassC implements FooInterface {
    public function foo($a, $b = 123) {}
}

interface BarInterface {
    public function bar();
}

trait MyTrait {
    abstract public function bar();
}

class ClassT {
    use MyTrait;

    public function bar() {}
}
