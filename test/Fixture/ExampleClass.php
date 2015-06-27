<?php

namespace AsgrimTest\Fixture {
    class ExampleClass
    {
        const MY_CONST_1 = 123;
        const MY_CONST_2 = 234;

        /**
         * @var int|float|\stdClass
         */
        private $privateProperty;

        /**
         * @var bool
         */
        protected $protectedProperty;

        /**
         * @var string
         */
        public $publicProperty;

        public static $publicStaticProperty;

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
