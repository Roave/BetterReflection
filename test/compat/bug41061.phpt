--TEST--
Reflection Bug #41061 ("visibility error" in ReflectionFunction::export())
--FILE--
<?php require 'vendor/autoload.php';

function foo() {
}
 
class bar {
    private function foo() {
    }
}

Reflection::export(\BetterReflection\Reflection\ReflectionFunction::createFromName('foo'));
Reflection::export(\BetterReflection\Reflection\ReflectionMethod::createFromName('bar', 'foo'));
?>
===DONE===
<?php require 'vendor/autoload.php'; exit(0); ?>
--EXPECTF--
Function [ <user> function foo ] {
  @@ %sbug41061.php 3 - 4
}

Method [ <user> private method foo ] {
  @@ %sbug41061.php 7 - 8
}

===DONE===
