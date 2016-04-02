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


$reflector = new \BetterReflection\Reflector\FunctionReflector(
    new BetterReflection\SourceLocator\Type\StringSourceLocator($source)
);

$functionInfo = $reflector->reflect('myFunction');

var_dump($functionInfo->getReturnType());

array_map(function (\BetterReflection\Reflection\ReflectionParameter $param) {
    var_dump($param->getType());
}, $functionInfo->getParameters());

?>
--EXPECTF--
object(BetterReflection\Reflection\ReflectionType)#%d (2) {
  ["type":"BetterReflection\Reflection\ReflectionType":private]=>
  object(phpDocumentor\Reflection\Types\Boolean)#%d (0) {
  }
  ["allowsNull":"BetterReflection\Reflection\ReflectionType":private]=>
  bool(false)
}
object(BetterReflection\Reflection\ReflectionType)#%d (2) {
  ["type":"BetterReflection\Reflection\ReflectionType":private]=>
  object(phpDocumentor\Reflection\Types\Integer)#%d (0) {
  }
  ["allowsNull":"BetterReflection\Reflection\ReflectionType":private]=>
  bool(false)
}
object(BetterReflection\Reflection\ReflectionType)#%d (2) {
  ["type":"BetterReflection\Reflection\ReflectionType":private]=>
  object(phpDocumentor\Reflection\Types\String_)#%d (0) {
  }
  ["allowsNull":"BetterReflection\Reflection\ReflectionType":private]=>
  bool(true)
}
