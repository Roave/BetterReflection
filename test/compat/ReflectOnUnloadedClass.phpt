--TEST--
Reflecting a class from a file that has not been loaded yet
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

var_dump(class_exists(UnloadedClass::class, false));

$reflector = new \BetterReflection\Reflector\ClassReflector(
    new BetterReflection\SourceLocator\Type\SingleFileSourceLocator(__DIR__ . '/assets/UnloadedClass.php')
);

$classInfo = $reflector->reflect(UnloadedClass::class);
var_dump($classInfo->getName());

var_dump(class_exists(UnloadedClass::class, false));

?>
--EXPECT--
bool(false)
string(13) "UnloadedClass"
bool(false)
