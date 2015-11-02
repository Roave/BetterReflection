--TEST--
Test ReflectionFunction::getClosure() function : basic functionality 
--FILE--
<?php require 'vendor/autoload.php';
/* Prototype  : public mixed ReflectionFunction::getClosure()
 * Description: Returns a dynamically created closure for the function 
 * Source code: ext/reflection/php_reflection.c
 * Alias to functions: 
 */

echo "*** Testing ReflectionFunction::getClosure() : basic functionality ***\n";

function foo()
{
	var_dump( "Inside foo function" );
}

function bar( $arg )
{
	var_dump( "Arg is " . $arg );
}

$func = \BetterReflection\Reflection\ReflectionFunction::createFromName( 'foo' );
$closure = $func->getClosure();
$closure();

$func = \BetterReflection\Reflection\ReflectionFunction::createFromName( 'bar' );
$closure = $func->getClosure();
$closure( 'succeeded' );

?>
===DONE===
--EXPECTF--
*** Testing ReflectionFunction::getClosure() : basic functionality ***
string(19) "Inside foo function"
string(16) "Arg is succeeded"
===DONE===
