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
var_dump($classInfo->getAst());

?>
--EXPECTF--
object(PhpParser\Node\Stmt\Class_)#%d (6) {
  ["type"]=>
  int(0)
  ["extends"]=>
  object(PhpParser\Node\Name)#%d (2) {
    ["parts"]=>
    array(1) {
      [0]=>
      string(12) "AnotherClass"
    }
    ["attributes":protected]=>
    array(2) {
      ["startLine"]=>
      int(3)
      ["endLine"]=>
      int(3)
    }
  }
  ["implements"]=>
  array(0) {
  }
  ["name"]=>
  string(15) "MyClassInString"
  ["stmts"]=>
  array(1) {
    [0]=>
    object(PhpParser\Node\Stmt\ClassMethod)#%d (7) {
      ["type"]=>
      int(1)
      ["byRef"]=>
      bool(false)
      ["name"]=>
      string(10) "someMethod"
      ["params"]=>
      array(0) {
      }
      ["returnType"]=>
      NULL
      ["stmts"]=>
      array(0) {
      }
      ["attributes":protected]=>
      array(2) {
        ["startLine"]=>
        int(5)
        ["endLine"]=>
        int(7)
      }
    }
  }
  ["attributes":protected]=>
  array(2) {
    ["startLine"]=>
    int(3)
    ["endLine"]=>
    int(8)
  }
}
