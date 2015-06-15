<?php

namespace AsgrimTest\Fixture {
    class ExampleClass
    {
        const MY_CONST_1 = 123;
        const MY_CONST_2 = 234;

        private $privateProperty;

        protected $protectedProperty;

        public $publicProperty;

        public function __construct()
        {
        }

        public function someMethod()
        {
        }
    }
}

namespace AsgrimTest\FixtureOther {
    class AnotherClass
    {
    }
}

namespace {
    class ClassWithExplicitGlobalNamespace
    {
    }
}
