--TEST--
Monkey patch a function (must be done before loaded)
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpParser\PrettyPrinter\Standard as CodePrinter;

$source = <<<'EOF'
<?php

function increment($a)
{
  return $a + 1;
}
EOF;

var_dump(function_exists('increment'));

$sourceLocator = new Rector\BetterReflection\SourceLocator\Type\StringSourceLocator(
    $source,
    (new Rector\BetterReflection\BetterReflection())->astLocator()
);

$reflector = new \Rector\BetterReflection\Reflector\FunctionReflector(
    $sourceLocator,
    new \Rector\BetterReflection\Reflector\ClassReflector($sourceLocator)
);

$functionInfo = $reflector->reflect('increment');

// Note, when outputting the code, formatting is lost, so the needless parens will not be expected
$functionInfo->setBodyFromString('return ($a + 2);');

var_dump($functionInfo->getBodyCode());

// Test that the code executes as expected also
eval((new \PhpParser\PrettyPrinter\Standard())->prettyPrint([$functionInfo->getAst()]));
var_dump(increment(5));

?>
--EXPECT--
bool(false)
string(14) "return $a + 2;"
int(7)
