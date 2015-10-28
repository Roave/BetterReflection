<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\AggregateSourceLocator;
use BetterReflection\SourceLocator\SingleFileSourceLocator;
use PhpParser\PrettyPrinter\Standard as CodePrinter;

// Create the reflection first (without loading)
$classInfo = (new ClassReflector(new AggregateSourceLocator([
    new SingleFileSourceLocator('MyClass.php'),
])))->reflect('MyClass');

// Override the body...!
$classInfo->getMethod('foo')->setBody(function () {
    return 4;
});

// Load the class...!!!!

$classCode = (new CodePrinter())->prettyPrint([$classInfo->getAst()]);

$tmpFile = tempnam(sys_get_temp_dir(), 'br-monkey-patching');
file_put_contents($tmpFile, '<?php ' . $classCode);
require_once($tmpFile);
unlink($tmpFile);

$c = new MyClass();
var_dump($c->foo()); // should be 4...!?!??
