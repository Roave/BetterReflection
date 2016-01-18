--TEST--
Reflection Bug #26695 (Reflection API does not recognize mixed-case class hints)
--FILE--
<?php require 'vendor/autoload.php';

class Foo {
}

class Bar {
  function demo(foo $f) {
  }
}

$class = \BetterReflection\Reflection\ReflectionClass::createFromName('bar');
$methods = $class->getMethods();
$params = $methods[0]->getParameters();

$class = $params[0]->getClass();

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($class->getName());
?>
===DONE===
--EXPECT--
string(3) "Foo"
===DONE===
