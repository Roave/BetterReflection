--TEST--
Bug #46205 (Closure - Memory leaks when ReflectionException is thrown)
--FILE--
<?php require 'vendor/autoload.php';
$x = \BetterReflection\Reflection\ReflectionMethod::createFromName('reflectionparameter', 'export');
$y = function() { };

try {
	$x->invokeArgs(\BetterReflection\Reflection\ReflectionParameter::createFromName('trim', 'str'), array($y, 1));
} catch (Exception $e) { }
?>
ok
--EXPECT--
ok
