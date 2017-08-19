<?php

namespace Roave\BetterReflectionTest\Fixture {
    /**
     * Some comments here
     */
    class ExampleClass
    {
        const MY_CONST_1 = 123;

        /**
         * Documentation for constant
         */
        const MY_CONST_2 = 234;
        public const MY_CONST_3 = 345;
        protected const MY_CONST_4 = 456;
        private const MY_CONST_5 = 567;

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
        public $publicProperty = __DIR__;

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

    class ClassWithTwoParents extends ClassWithParent
    {
    }

    abstract class AbstractClass
    {
    }

    final class FinalClass
    {
    }

    trait ExampleTrait
    {
    }

    interface ExampleInterface
    {
    }
}

namespace Roave\BetterReflectionTest\FixtureOther {
    class AnotherClass
    {
    }
}

namespace {
    class ClassWithExplicitGlobalNamespace
    {
    }
}
