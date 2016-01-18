--TEST--
Test ReflectionFunction::getClosure() function : error functionality
--FILE--
<?php require 'vendor/autoload.php';
/* Prototype  : public mixed ReflectionFunction::getClosure()
 * Description: Returns a dynamically created closure for the function
 * Source code: ext/reflection/php_reflection.c
 * Alias to functions:
 */

echo "*** Testing ReflectionFunction::getClosure() : error conditions ***\n";

function foo()
{
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump( "Inside foo function" );
}

$func = \BetterReflection\Reflection\ReflectionFunction::createFromName( 'foo' );
$closure = $func->getClosure('bar');

?>
===DONE===
--EXPECTF--
*** Testing ReflectionFunction::getClosure() : error conditions ***

Warning: ReflectionFunction::getClosure() expects exactly 0 parameters, 1 given in %s on line %d
===DONE===
