--TEST--
ReflectionClass::isIterable()
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--FILE--
<?php
Interface ExtendsIterator extends Iterator {
}
Interface ExtendsIteratorAggregate extends IteratorAggregate {
}
Class IteratorImpl implements Iterator {
	public function next() {}
	public function key() {}
	public function rewind() {}
	public function current() {}
	public function valid() {}
}
Class IterarorAggregateImpl implements IteratorAggregate {
	public function getIterator() {}
}
Class ExtendsIteratorImpl extends IteratorImpl {
}
Class ExtendsIteratorAggregateImpl extends IterarorAggregateImpl {
}
Class A {
}

$classes = array('Traversable', 'Iterator', 'IteratorAggregate', 'ExtendsIterator', 'ExtendsIteratorAggregate', 
	  'IteratorImpl', 'IterarorAggregateImpl', 'ExtendsIteratorImpl', 'ExtendsIteratorAggregateImpl', 'A');

foreach($classes as $class) {
	$rc = new ReflectionClass($class);
	echo "Is $class iterable? ";
	var_dump($rc->isIterable());
}

echo "\nTest invalid params:\n";
$rc = new ReflectionClass('IteratorImpl');
var_dump($rc->isIterable(null));
var_dump($rc->isIterable(null, null));
var_dump($rc->isIterable(1));
var_dump($rc->isIterable(1.5));
var_dump($rc->isIterable(true));
var_dump($rc->isIterable('X'));
var_dump($rc->isIterable(null));

echo "\nTest static invocation:\n";
ReflectionClass::isIterable();

?>
--EXPECTF--
Is Traversable iterable? bool(false)
Is Iterator iterable? bool(false)
Is IteratorAggregate iterable? bool(false)
Is ExtendsIterator iterable? bool(false)
Is ExtendsIteratorAggregate iterable? bool(false)
Is IteratorImpl iterable? bool(true)
Is IterarorAggregateImpl iterable? bool(true)
Is ExtendsIteratorImpl iterable? bool(true)
Is ExtendsIteratorAggregateImpl iterable? bool(true)
Is A iterable? bool(false)

Test invalid params:

Warning: ReflectionClass::isIterable() expects exactly 0 parameters, 1 given in %s on line 34
NULL

Warning: ReflectionClass::isIterable() expects exactly 0 parameters, 2 given in %s on line 35
NULL

Warning: ReflectionClass::isIterable() expects exactly 0 parameters, 1 given in %s on line 36
NULL

Warning: ReflectionClass::isIterable() expects exactly 0 parameters, 1 given in %s on line 37
NULL

Warning: ReflectionClass::isIterable() expects exactly 0 parameters, 1 given in %s on line 38
NULL

Warning: ReflectionClass::isIterable() expects exactly 0 parameters, 1 given in %s on line 39
NULL

Warning: ReflectionClass::isIterable() expects exactly 0 parameters, 1 given in %s on line 40
NULL

Test static invocation:

Fatal error: Uncaught Error: Non-static method ReflectionClass::isIterable() cannot be called statically in %s:43
Stack trace:
#0 {main}
  thrown in %s on line 43