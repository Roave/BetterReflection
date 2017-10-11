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

$sourceLocator = new Rector\BetterReflection\SourceLocator\Type\StringSourceLocator(
    $source,
    (new Rector\BetterReflection\BetterReflection())->astLocator()
);

$reflector = new \Rector\BetterReflection\Reflector\ClassReflector($sourceLocator);

$classInfo = $reflector->reflect(MyClassInString::class);
var_dump($classInfo->getAst() instanceof \PhpParser\Node\Stmt\Class_);

?>
--EXPECTF--
bool(true)
