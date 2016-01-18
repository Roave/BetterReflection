--TEST--
ReflectionClass::getInterfaces() - odd ampersand behaviour.
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--SKIPIF--
skip
<?php
// Skipping this as too slow currently :(
// see https://github.com/Roave/BetterReflection/issues/146
--FILE--
<?php require 'vendor/autoload.php';

echo "An object is in an array and is referenced. As expected, // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dumping the array shows '&':\n";
$a = array(new stdclass);
$b =& $a[0];
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($a);

echo "Naturally, this remains true if we modify the object:\n";
$a[0]->x = 1;
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($a);


echo "\n\nObtain the array of interfaces implemented by C.\n";
interface I {}
class C implements I {}
$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('C');
$a = $rc->getInterfaces();
echo "The result is an array in which each element is an object (an instance of ReflectionClass)\n";
echo "// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dumping this array shows that the elements are referenced. By what?\n";
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($a);

echo "Modify the object, and it is apparently no longer referenced.\n";
$a['I']->x = 1;
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($a);

?>
--EXPECTF--
An object is in an array and is referenced. As expected, // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dumping the array shows '&':
array(1) {
  [0]=>
  &object(stdClass)#%d (0) {
  }
}
Naturally, this remains true if we modify the object:
array(1) {
  [0]=>
  &object(stdClass)#%d (1) {
    ["x"]=>
    int(1)
  }
}


Obtain the array of interfaces implemented by C.
The result is an array in which each element is an object (an instance of ReflectionClass)
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dumping this array shows that the elements are referenced. By what?
array(1) {
  ["I"]=>
  object(ReflectionClass)#%d (1) {
    ["name"]=>
    string(1) "I"
  }
}
Modify the object, and it is apparently no longer referenced.
array(1) {
  ["I"]=>
  object(ReflectionClass)#%d (2) {
    ["name"]=>
    string(1) "I"
    ["x"]=>
    int(1)
  }
}
