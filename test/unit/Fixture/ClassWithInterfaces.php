<?php

namespace Rector\BetterReflectionTest\ClassWithInterfaces {
    use Rector\BetterReflectionTest\ClassWithInterfacesOther\B as ImportedB;
    use Rector\BetterReflectionTest\ClassWithInterfacesOther;
    use Rector\BetterReflectionTest\ClassWithInterfacesExtendingInterfaces;

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

namespace Rector\BetterReflectionTest\ClassWithInterfacesOther {
    interface B
    {
    }

    interface D
    {
    }
}

namespace Rector\BetterReflectionTest\ClassWithInterfacesExtendingInterfaces {
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
