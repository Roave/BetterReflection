<?php

namespace BetterReflectionTest\Fixture {
    /**
     * Some comments here
     */
    class ExampleClass
    {
        const MY_CONST_1 = 123;
        const MY_CONST_2 = 234;

        /**
         * @var int|float|\stdClass
         */
        private $privateProperty;

        /**
         * @var bool|bool[]|bool[][]
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

    class ClassWithParent extends ExampleClass
    {
    }
}

namespace BetterReflectionTest\FixtureOther {
    class AnotherClass
    {
    }
}

namespace {
    class ClassWithExplicitGlobalNamespace
    {
    }
}
