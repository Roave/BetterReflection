--TEST--
Produce the AST for a specific function
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$source = <<<'EOF'
<?php

function adder($a, $b)
{
    return $a + $b;
}
EOF;

$sourceLocator = new Roave\BetterReflection\SourceLocator\Type\StringSourceLocator(
    $source,
    (new Roave\BetterReflection\Configuration())->astLocator()
);

$reflector = new \Roave\BetterReflection\Reflector\FunctionReflector(
    $sourceLocator,
    new \Roave\BetterReflection\Reflector\ClassReflector($sourceLocator)
);

$functionInfo = $reflector->reflect('adder');
var_dump($functionInfo->getAst() instanceof PhpParser\Node\Stmt\Function_);

?>
--EXPECTF--
bool(true)
