--TEST--
ReflectionFunction::getFileName()
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--FILE--
<?php require 'vendor/autoload.php';

/**
 * my doc comment
 */
function foo () {
	static $c;
	static $a = 1;
	static $b = "hello";
	$d = 5;
}

/***
 * not a doc comment
 */
function bar () {}


function dumpFuncInfo($name) {
	$funcInfo = \BetterReflection\Reflection\ReflectionFunction::createFromName($name);
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($funcInfo->getFileName());
}

dumpFuncInfo('foo');
dumpFuncInfo('bar');
dumpFuncInfo('extract');

?>
--EXPECTF--
string(%d) "%sReflectionFunction_getFileName.002.php"
string(%d) "%sReflectionFunction_getFileName.002.php"
bool(false)

