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

$sourceLocator = new Rector\BetterReflection\SourceLocator\Type\StringSourceLocator(
    $source,
    (new Rector\BetterReflection\BetterReflection())->astLocator()
);

$reflector = new \Rector\BetterReflection\Reflector\FunctionReflector(
    $sourceLocator,
    new \Rector\BetterReflection\Reflector\ClassReflector($sourceLocator)
);

$functionInfo = $reflector->reflect('myFunction');

var_dump($functionInfo->getReturnType());

array_map(function (\Rector\BetterReflection\Reflection\ReflectionParameter $param) {
    var_dump($param->getType());
}, $functionInfo->getParameters());

?>
--EXPECTF--
object(Rector\BetterReflection\Reflection\ReflectionType)#%d (2) {
  ["type":"Rector\BetterReflection\Reflection\ReflectionType":private]=>
  string(4) "bool"
  ["allowsNull":"Rector\BetterReflection\Reflection\ReflectionType":private]=>
  bool(false)
}
object(Rector\BetterReflection\Reflection\ReflectionType)#%d (2) {
  ["type":"Rector\BetterReflection\Reflection\ReflectionType":private]=>
  string(3) "int"
  ["allowsNull":"Rector\BetterReflection\Reflection\ReflectionType":private]=>
  bool(false)
}
object(Rector\BetterReflection\Reflection\ReflectionType)#%d (2) {
  ["type":"Rector\BetterReflection\Reflection\ReflectionType":private]=>
  string(6) "string"
  ["allowsNull":"Rector\BetterReflection\Reflection\ReflectionType":private]=>
  bool(true)
}
