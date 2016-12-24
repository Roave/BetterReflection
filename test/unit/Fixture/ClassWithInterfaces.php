<?php

namespace Roave\BetterReflectionTest\ClassWithInterfaces {
    use Roave\BetterReflectionTest\ClassWithInterfacesOther\B as ImportedB;
    use Roave\BetterReflectionTest\ClassWithInterfacesOther;
    use Roave\BetterReflectionTest\ClassWithInterfacesExtendingInterfaces;

    interface A {}
    interface B {}

    class ExampleClass implements A, ImportedB, C, ClassWithInterfacesOther\D, \E
    {
    }

    interface C {}

    class SubExampleClass extends ExampleClass {}
    class SubSubExampleClass extends SubExampleClass implements ImportedB, B {}
    class ExampleImplementingCompositeInterface implements ClassWithInterfacesExtendingInterfaces\D {}
}

namespace Roave\BetterReflectionTest\ClassWithInterfacesOther {
    interface B
    {
    }

    interface D
    {
    }
}

namespace Roave\BetterReflectionTest\ClassWithInterfacesExtendingInterfaces {
    interface A
    {
    }

    interface B
    {
    }

    interface C extends B
    {
    }

    interface D extends C, A
    {
    }
}

namespace {
    interface E
    {
    }
}
