--TEST--
Reflecting a class from a string
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$source = <<<EOF
<?php

class MyClassInString {}
EOF;


$reflector = new \BetterReflection\Reflector\ClassReflector(
    new BetterReflection\SourceLocator\Type\StringSourceLocator($source)
);

$classInfo = $reflector->reflect(MyClassInString::class);
var_dump($classInfo->getName());

?>
--EXPECT--
string(15) "MyClassInString"
