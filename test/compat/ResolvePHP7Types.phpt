--TEST--
Ability to resolve types in PHP 7
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$source = <<<'EOF'
<?php

function myFunction(int $a, string $b = null): bool
{
}
EOF;


$reflector = new \Roave\BetterReflection\Reflector\FunctionReflector(
    new Roave\BetterReflection\SourceLocator\Type\StringSourceLocator($source)
);

$functionInfo = $reflector->reflect('myFunction');

var_dump($functionInfo->getReturnType());

array_map(function (\Roave\BetterReflection\Reflection\ReflectionParameter $param) {
    var_dump($param->getType());
}, $functionInfo->getParameters());

?>
--EXPECTF--
object(Roave\BetterReflection\Reflection\ReflectionType)#%d (2) {
  ["type":"Roave\BetterReflection\Reflection\ReflectionType":private]=>
  string(4) "bool"
  ["allowsNull":"Roave\BetterReflection\Reflection\ReflectionType":private]=>
  bool(false)
}
object(Roave\BetterReflection\Reflection\ReflectionType)#%d (2) {
  ["type":"Roave\BetterReflection\Reflection\ReflectionType":private]=>
  string(3) "int"
  ["allowsNull":"Roave\BetterReflection\Reflection\ReflectionType":private]=>
  bool(false)
}
object(Roave\BetterReflection\Reflection\ReflectionType)#%d (2) {
  ["type":"Roave\BetterReflection\Reflection\ReflectionType":private]=>
  string(6) "string"
  ["allowsNull":"Roave\BetterReflection\Reflection\ReflectionType":private]=>
  bool(true)
}
