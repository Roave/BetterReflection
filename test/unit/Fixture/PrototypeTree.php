<?php

namespace {
    interface FooInterface
    {
        public function foo($a, $b);
    }

    abstract class ClassA implements FooInterface
    {
        abstract public function foo($a, $b);
    }

    class ClassB extends ClassA
    {
        public function foo($a, $b = 123)
        {
        }
    }

    class ClassC implements FooInterface
    {
        public function foo($a, $b = 123)
        {
        }

        public function boo()
        {
        }

        private function zoo()
        {
        }
    }

    class ClassD extends ClassC
    {
        public function boo()
        {
        }

        protected function zoo()
        {
        }
    }

    class ClassE extends ClassD
    {
        public function boo()
        {
        }

        protected function zoo()
        {
        }
    }

    class ClassF extends ClassE
    {
        protected function zoo()
        {
        }
    }

    interface BarInterface
    {
        public function bar();
    }

    trait MyTrait
    {
        abstract public function bar();
    }

    class ClassT
    {
        use MyTrait;

        public function bar()
        {
        }
    }
}

namespace Zoom {
    interface FooInterface
    {
        public function foo($a, $b);
    }

    abstract class A
    {
        abstract public function foo($a, $b);
    }

    class B extends A implements FooInterface
    {
        public function foo($a, $b)
        {
        }
    }
}


namespace Xoom {
    interface FooInterface
    {
        public function foo($a, $b);
    }

    abstract class A implements FooInterface
    {
        abstract public function foo($a, $b);
    }

    class B extends A
    {
        public function foo($a, $b)
        {
        }
    }
}

namespace Foom {
    interface Foo
    {
        public function foo($a, $b);
    }

    class A implements Foo
    {
        public function foo($a, $b)
        {
        }
    }
}

namespace Boom {
    interface Foo {}
    interface Bar {}
    interface Boo extends Bar {}

    class A implements Foo {}
    class B extends A implements Boo {}
}

namespace Construct {
    class Foo
    {
        public function __construct(int $i)
        {
        }
    }

    class Bar extends Foo
    {
        public function __construct(string $s)
        {
        }
    }

    abstract class Lorem
    {
        abstract public function __construct(int $i);
    }

    class Ipsum extends Lorem
    {
        public function __construct(int $i)
        {
        }
    }
}

namespace Traits {
    interface FooInterface
    {
        public function doFoo(): void;
    }

    trait FooTrait
    {
        public function doFoo(): void
        {
        }
    }

    class Foo implements FooInterface
    {
        use FooTrait;
    }
}
