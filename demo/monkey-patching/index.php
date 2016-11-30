<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PhpParser\PrettyPrinter\Standard as CodePrinter;

// Create the reflection first (without loading)
$classInfo = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/MyClass.php')))->reflect('MyClass');

// Override the body...!
$classInfo->getMethod('foo')->setBodyFromClosure(function () {
    return 4;
});

// Load the class...!!!!
$classCode = (new CodePrinter())->prettyPrint([$classInfo->getAst()]);
eval($classCode);

$c = new MyClass();
echo $c->foo() . "\n"; // should be 4...!?!??
