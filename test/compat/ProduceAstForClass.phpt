--TEST--
Produce the AST for the specific class
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$source = <<<EOF
<?php

class MyClassInString extends AnotherClass
{
    public function someMethod()
    {
    }
}
EOF;


$reflector = new \BetterReflection\Reflector\ClassReflector(
    new BetterReflection\SourceLocator\Type\StringSourceLocator($source)
);

$classInfo = $reflector->reflect(MyClassInString::class);
var_dump($classInfo->getAst() instanceof \PhpParser\Node\Stmt\Class_);

?>
--EXPECTF--
bool(true)
