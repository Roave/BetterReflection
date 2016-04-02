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


$reflector = new \BetterReflection\Reflector\FunctionReflector(
    new BetterReflection\SourceLocator\Type\StringSourceLocator($source)
);

$functionInfo = $reflector->reflect('adder');
var_dump($functionInfo->getAst());

?>
--EXPECTF--
object(PhpParser\Node\Stmt\Function_)#%d (6) {
  ["byRef"]=>
  bool(false)
  ["name"]=>
  string(5) "adder"
  ["params"]=>
  array(2) {
    [0]=>
    object(PhpParser\Node\Param)#%d (7) {
      ["type"]=>
      NULL
      ["byRef"]=>
      bool(false)
      ["variadic"]=>
      bool(false)
      ["name"]=>
      string(1) "a"
      ["default"]=>
      NULL
      ["attributes":protected]=>
      array(2) {
        ["startLine"]=>
        int(3)
        ["endLine"]=>
        int(3)
      }
      ["isOptional"]=>
      bool(false)
    }
    [1]=>
    object(PhpParser\Node\Param)#%d (7) {
      ["type"]=>
      NULL
      ["byRef"]=>
      bool(false)
      ["variadic"]=>
      bool(false)
      ["name"]=>
      string(1) "b"
      ["default"]=>
      NULL
      ["attributes":protected]=>
      array(2) {
        ["startLine"]=>
        int(3)
        ["endLine"]=>
        int(3)
      }
      ["isOptional"]=>
      bool(false)
    }
  }
  ["returnType"]=>
  NULL
  ["stmts"]=>
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
  ["attributes":protected]=>
  array(2) {
    ["startLine"]=>
    int(3)
    ["endLine"]=>
    int(6)
  }
}
