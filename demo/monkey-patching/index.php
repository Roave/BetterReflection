<?php

require_once __DIR__ . '/../../vendor/autoload.php';


use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\Util\Autoload;

Autoload::initialise();

// Create the reflection first (without loading)
$classInfo = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/MyClass.php')))->reflect('MyClass');
Autoload::addClass($classInfo);

// Override the body...!
$classInfo->getMethod('foo')->setBodyFromClosure(function () {
    return 4;
});

$c = new MyClass();
echo $c->foo() . "\n"; // should be 4...!?!??
