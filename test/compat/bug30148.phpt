--TEST--
Reflection Bug #30148 (ReflectionMethod->isConstructor() fails for inherited classes)
--FILE--
<?php require 'vendor/autoload.php';

class Root
{
	function Root() {}
}
class Base extends Root
{
	function __construct() {}
}
class Derived extends Base
{
}
$a = \BetterReflection\Reflection\ReflectionMethod::createFromName('Root','Root');
$b = \BetterReflection\Reflection\ReflectionMethod::createFromName('Base','Root');
$c = \BetterReflection\Reflection\ReflectionMethod::createFromName('Base','__construct');
$d = \BetterReflection\Reflection\ReflectionMethod::createFromName('Derived','Root');
$e = \BetterReflection\Reflection\ReflectionMethod::createFromName('Derived','__construct');
var_dump($a->isConstructor());
var_dump($b->isConstructor());
var_dump($c->isConstructor());
var_dump($d->isConstructor());
var_dump($e->isConstructor());
?>
===DONE===
--EXPECTF--
Deprecated: Methods with the same name as their class will not be constructors in a future version of PHP; Root has a deprecated constructor in %s on line %d
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
===DONE===
