<?php

namespace BetterReflectionTest\ClassWithInterfaces {
    use BetterReflectionTest\ClassWithInterfacesOther\B as ImportedB;
    use BetterReflectionTest\ClassWithInterfacesOther;

    interface A {}
    interface B {}

    class ExampleClass implements A, ImportedB, C, ClassWithInterfacesOther\D, \E
    {
    }

    interface C {}

    class SubExampleClass extends ExampleClass {}
    class SubSubExampleClass extends SubExampleClass implements ImportedB, B {}
}

namespace BetterReflectionTest\ClassWithInterfacesOther {
    interface B
    {
    }

    interface D
    {
    }
}

namespace {
    interface E
    {
    }
}
