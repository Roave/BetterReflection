--TEST--
Reflection Bug #30856 (ReflectionClass::getStaticProperties segfaults)
--FILE--
<?php require 'vendor/autoload.php';
class bogus {
        const C = 'test';
        static $a = bogus::C;
}

$class = \BetterReflection\Reflection\ReflectionClass::createFromName('bogus');

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($class->getStaticProperties());
?>
===DONE===
--EXPECT--
array(1) {
  ["a"]=>
  string(4) "test"
}
===DONE===
