--TEST--
ReflectionClass::isIterable() variations
--CREDITS--
Felix De Vliegher <felix.devliegher@gmail.com>
--FILE--
<?php

class BasicClass {}

function dump_Iterable($obj)
{
	$reflection = new ReflectionClass($obj);
	var_dump($reflection->isIterable());
}

$basicClass = new BasicClass();
$stdClass = new StdClass();

dump_Iterable($basicClass);
dump_Iterable($stdClass);

?>
--EXPECT--
bool(false)
bool(false)
