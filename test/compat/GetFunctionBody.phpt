--TEST--
Retrieve the AST or code directly from a function body
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$source = <<<'EOF'
<?php

function adder(int $a, int $b)
{
    return $a + $b;
}
EOF;


$reflector = new \BetterReflection\Reflector\FunctionReflector(
    new BetterReflection\SourceLocator\Type\StringSourceLocator($source)
);

$functionInfo = $reflector->reflect('adder');
var_dump($functionInfo->getName());

var_dump($functionInfo->getBodyAst());
var_dump($functionInfo->getBodyCode());

?>
--EXPECTF--
string(5) "adder"
array(1) {
  [0]=>
  object(PhpParser\Node\Stmt\Return_)#%d (2) {
    ["expr"]=>
    object(PhpParser\Node\Expr\BinaryOp\Plus)#%d (3) {
      ["left"]=>
      object(PhpParser\Node\Expr\Variable)#%d (2) {
        ["name"]=>
        string(1) "a"
        ["attributes":protected]=>
        array(2) {
          ["startLine"]=>
          int(5)
          ["endLine"]=>
          int(5)
        }
      }
      ["right"]=>
      object(PhpParser\Node\Expr\Variable)#%d (2) {
        ["name"]=>
        string(1) "b"
        ["attributes":protected]=>
        array(2) {
          ["startLine"]=>
          int(5)
          ["endLine"]=>
          int(5)
        }
      }
      ["attributes":protected]=>
      array(2) {
        ["startLine"]=>
        int(5)
        ["endLine"]=>
        int(5)
      }
    }
    ["attributes":protected]=>
    array(2) {
      ["startLine"]=>
      int(5)
      ["endLine"]=>
      int(5)
    }
  }
}
string(15) "return $a + $b;"
