--TEST--
ReflectionClass::isIterable() basic
--CREDITS--
Felix De Vliegher <felix.devliegher@gmail.com>, Marc Veldman <marc@ibuildings.nl>
--FILE--
<?php

class IteratorClass implements Iterator {
	public function __construct() { }
	public function key() {}
	public function current() {}
	function next()	{}
	function valid() {}
	function rewind() {}
}
class DerivedClass extends IteratorClass {}
class NonIterator {}

function dump_Iterable($class) {
	$reflection = new ReflectionClass($class);
	var_dump($reflection->isIterable());
}

$classes = array("ArrayObject", "IteratorClass", "DerivedClass", "NonIterator");
foreach ($classes as $class) {
	echo "Is $class Iterable? ";
	dump_Iterable($class);
}
?>
--EXPECT--
Is ArrayObject Iterable? bool(true)
Is IteratorClass Iterable? bool(true)
Is DerivedClass Iterable? bool(true)
Is NonIterator Iterable? bool(false)
