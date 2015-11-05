<?php

// Load a standard (internal) class
namespace Example1
{
    require_once __DIR__ . '/../../vendor/autoload.php';

    use BetterReflection\Reflection\ReflectionClass;

    $reflection = ReflectionClass::createFromName('stdClass');
    var_dump($reflection->getName()); // stdClass
    var_dump($reflection->isInternal()); // true
}

// Load an autoloadable class
namespace Example2
{
    require_once __DIR__ . '/../../vendor/autoload.php';

    use BetterReflection\Reflection\ReflectionClass;

    $reflection = ReflectionClass::createFromName(ReflectionClass::class);
    var_dump($reflection->getName()); // BetterReflection\Reflection\ReflectionClass
    var_dump($reflection->isInternal()); // false
}

// Loading a specific file (not from autoloader)
namespace Example3
{
    require_once __DIR__ . '/../../vendor/autoload.php';

    use BetterReflection\Reflector\ClassReflector;
    use BetterReflection\SourceLocator\Type\AggregateSourceLocator;
    use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

    $reflector = new ClassReflector(new AggregateSourceLocator([
        new SingleFileSourceLocator('MyClass.php'),
    ]));

    $reflection = $reflector->reflect('MyClass');

    var_dump($reflection->getName()); // MyClass
    var_dump($reflection->getProperty('foo')->isPrivate()); // true
    var_dump($reflection->getProperty('foo')->getDocBlockTypeStrings()[0]); // string
    var_dump($reflection->getMethod('getFoo')->getDocBlockReturnTypes()[0]->__toString()); // string
}
