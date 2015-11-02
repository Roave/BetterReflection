--TEST--
ReflectionClass::getInterfaceNames()
--CREDITS--
Michelangelo van Dam <dragonbe@gmail.com>
#testfest roosendaal on 2008-05-10
--FILE--
<?php require 'vendor/autoload.php';
interface Foo { }

interface Bar { }

class Baz implements Foo, Bar { }

$rc1 = \BetterReflection\Reflection\ReflectionClass::createFromName("Baz");
var_dump($rc1->getInterfaceNames());
?>
--EXPECT--
array(2) {
  [0]=>
  string(3) "Foo"
  [1]=>
  string(3) "Bar"
}
