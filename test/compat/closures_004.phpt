--TEST--
Reflection on closures: Segfault with getClosure() on closure itself
--FILE-- 
<?php require 'vendor/autoload.php';
$closure = function() { echo "Invoked!\n"; };

$method = \BetterReflection\Reflection\ReflectionFunction::createFromName ($closure);

$closure2 = $method->getClosure ();

$closure2 ();
$closure2->__invoke ();

unset ($closure);

$closure2 ();
$closure2->__invoke ();

$closure = function() { echo "Invoked!\n"; };

$method = \BetterReflection\Reflection\ReflectionMethod::createFromName ($closure, '__invoke');
$closure2 = $method->getClosure ($closure);

$closure2 ();
$closure2->__invoke ();

unset ($closure);

$closure2 ();
$closure2->__invoke ();

?>
===DONE===
--EXPECTF--
Invoked!
Invoked!
Invoked!
Invoked!
Invoked!
Invoked!
Invoked!
Invoked!
===DONE===
