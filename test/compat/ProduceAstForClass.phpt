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

$sourceLocator = new Roave\BetterReflection\SourceLocator\Type\StringSourceLocator(
    $source,
    (new Roave\BetterReflection\BetterReflection())->astLocator()
);

$reflector = new \Roave\BetterReflection\Reflector\DefaultReflector($sourceLocator);

$classInfo = $reflector->reflectClass(MyClassInString::class);
var_dump($classInfo->getAst() instanceof \PhpParser\Node\Stmt\Class_);

?>
--EXPECTF--
bool(true)
