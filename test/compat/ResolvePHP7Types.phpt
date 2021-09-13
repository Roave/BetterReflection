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

$sourceLocator = new Roave\BetterReflection\SourceLocator\Type\StringSourceLocator(
    $source,
    (new Roave\BetterReflection\BetterReflection())->astLocator()
);

$reflector = new \Roave\BetterReflection\Reflector\FunctionReflector(
    $sourceLocator,
    new \Roave\BetterReflection\Reflector\ClassReflector($sourceLocator)
);

$functionInfo = $reflector->reflect('myFunction');

$returnType = $functionInfo->getReturnType();

var_dump([
    'builtIn' => $returnType->isBuiltin(),
    'type' => $returnType->__toString(),
]);

array_map(function (\Roave\BetterReflection\Reflection\ReflectionParameter $param) {
    $type = $param->getType();

    var_dump([
        'builtIn' => $type->isBuiltin(),
        'type' => $type->__toString(),
    ]);
}, $functionInfo->getParameters());

?>
--EXPECTF--
array(2) {
  ["builtIn"]=>
  bool(true)
  ["type"]=>
  string(4) "bool"
}
array(2) {
  ["builtIn"]=>
  bool(true)
  ["type"]=>
  string(3) "int"
}
array(2) {
  ["builtIn"]=>
  bool(true)
  ["type"]=>
  string(7) "?string"
}
