--TEST--
ReflectionFunction::isGenerator()
--FILE--
<?php require 'vendor/autoload.php';

$closure1 = function() {return "this is a closure"; };
$closure2 = function($param) {
	yield $param;
};

$rf1 = \BetterReflection\Reflection\ReflectionFunction::createFromName($closure1);
var_dump($rf1->isGenerator());

$rf2 = \BetterReflection\Reflection\ReflectionFunction::createFromName($closure2);
var_dump($rf2->isGenerator());

function func1() {
	return 'func1';
}

function func2() {
	yield 'func2';
}

$rf1 = \BetterReflection\Reflection\ReflectionFunction::createFromName('func1');
var_dump($rf1->isGenerator());

$rf2 = \BetterReflection\Reflection\ReflectionFunction::createFromName('func2');
var_dump($rf2->isGenerator());


class Foo {
	public function f1() {
	}

	public function f2() {
		yield;
	}
}

$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('Foo');
foreach($rc->getMethods() as $m) {
	var_dump($m->isGenerator());
}
?>
--EXPECTF--
bool(false)
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
