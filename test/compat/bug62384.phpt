--TEST--
Bug #62384 (Attempting to invoke a Closure more than once causes segfaul)
--FILE--
<?php require 'vendor/autoload.php';

$closure1   = function($val){ return $val; };
$closure2   = function($val){ return $val; };

$reflection_class   = \BetterReflection\Reflection\ReflectionClass::createFromName($closure1);
$reflection_method  = $reflection_class->getMethod('__invoke');

$arguments1         = array('hello');
$arguments2         = array('world');

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($reflection_method->invokeArgs($closure1, $arguments1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($reflection_method->invokeArgs($closure2, $arguments2));

?>
--EXPECT--
string(5) "hello"
string(5) "world"
