--TEST--
Ability to resolve types from docblocks
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$source = <<<'EOF'
<?php

class MyClassInString {
    /**
     * @param int $a
     * @param string $b
     * @return bool
     */
    public function myMethod($a, $b)
    {
    }
}
EOF;

$reflector = new \Rector\BetterReflection\Reflector\ClassReflector(
    new Rector\BetterReflection\SourceLocator\Type\StringSourceLocator(
        $source,
        (new Rector\BetterReflection\BetterReflection())->astLocator()
    )
);

$classInfo = $reflector->reflect(MyClassInString::class);

$methodInfo = $classInfo->getMethod('myMethod');

var_dump($methodInfo->getDocBlockReturnTypes());

array_map(function (\Rector\BetterReflection\Reflection\ReflectionParameter $param) {
    var_dump($param->getDocBlockTypeStrings());
}, $methodInfo->getParameters());

?>
--EXPECTF--
array(1) {
  [0]=>
  object(phpDocumentor\Reflection\Types\Boolean)#%d (0) {
  }
}
array(1) {
  [0]=>
  string(3) "int"
}
array(1) {
  [0]=>
  string(6) "string"
}
