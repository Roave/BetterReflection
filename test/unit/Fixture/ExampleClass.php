<?php

namespace Roave\BetterReflectionTest\Fixture {
    /**
     * Unused class comment
     */
    /**
     * This class comment should be used.
     */
    class ExampleClass
    {
        const MY_CONST_1 = 123;

        /**
         * Unused documentation for constant
         */
        /**
         * This comment for constant should be used.
         */
        const MY_CONST_2 = 234;
        public const MY_CONST_3 = 345;
        protected const MY_CONST_4 = 456;
        private const MY_CONST_5 = 567;
        public final const MY_CONST_6 = 678;
        protected final const MY_CONST_7 = 789;

        /**
         * @var int|float|\stdClass
         */
        private $privateProperty;

        /**
         * @var bool|bool[]|bool[][]
         */
        protected $protectedProperty;

        /** Unused property comment */
        /**
         * @var string
         */
        public $publicProperty = __DIR__;

        public readonly int $readOnlyProperty;

        public static $publicStaticProperty;

        protected static $protectedStaticProperty;

        private static $privateStaticProperty;

        public function __construct(/** Some doccomment */ private ?int $promotedProperty = 123, $noPromotedProperty = null, private $promotedProperty2 = null)
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

    readonly class ReadOnlyClass
    {
        private int $property;
    }

    trait ExampleTrait
    {
    }

    interface ExampleInterface
    {
    }

    class ExampleClassWhereConstructorIsNotFirstMethod
    {
        public function someMethod()
        {
        }

        public function __construct()
        {
        }
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
