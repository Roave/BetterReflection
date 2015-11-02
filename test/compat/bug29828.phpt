--TEST--
Reflection Bug #29828 (Interfaces no longer work)
--FILE--
<?php require 'vendor/autoload.php';

interface Bla
{
	function bla();
}

class BlaMore implements Bla
{
	function bla()
	{
		echo "Hello\n";
	}
}

$r = \BetterReflection\Reflection\ReflectionClass::createFromName('BlaMore');

var_dump(count($r->getMethods()));
var_dump($r->getMethod('bla')->isConstructor());
var_dump($r->getMethod('bla')->isAbstract());

$o=new BlaMore;
$o->bla();

?>
===DONE===
--EXPECT--
int(1)
bool(false)
bool(false)
Hello
===DONE===
