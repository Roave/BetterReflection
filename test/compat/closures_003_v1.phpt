--TEST--
Reflection on closures: Segfaults with getParameters() and getDeclaringFunction()
--FILE-- 
<?php require 'vendor/autoload.php';

$closure = function($a, $b = 0) { };

$method = \BetterReflection\Reflection\ReflectionFunction::createFromName ($closure);
$params = $method->getParameters ();
unset ($method);
$method = $params[0]->getDeclaringFunction ();
unset ($params);
echo $method->getName ()."\n";

$parameter = \BetterReflection\Reflection\ReflectionParameter::createFromName ($closure, 'b');
$method = $parameter->getDeclaringFunction ();
unset ($parameter);
echo $method->getName ()."\n";

?>
===DONE===
--EXPECTF--
{closure}
{closure}
===DONE===
