--TEST--
ReflectionParameter::__toString()
--CREDITS--
Stefan Koopmanschap <stefan@stefankoopmanschap.nl>
--FILE--
<?php require 'vendor/autoload.php';
function ReflectionParameterTest($test, $test2 = null, ...$test3) {
	echo $test;
}
$reflect = \BetterReflection\Reflection\ReflectionFunction::createFromName('ReflectionParameterTest');
$params = $reflect->getParameters();
foreach($params as $key => $value) {
	echo $value->__toString() . "\n";
}
?>
==DONE==
--EXPECT--
Parameter #0 [ <required> $test ]
Parameter #1 [ <optional> $test2 = NULL ]
Parameter #2 [ <optional> ...$test3 ]
==DONE==
