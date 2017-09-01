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

    class A implements Foo {}
    class B extends A implements Bar {}
}
