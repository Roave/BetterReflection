--TEST--
ReflectionFunction::isGenerator()
--FILE--
<?php require 'vendor/autoload.php';

$closure1 = function() {return "this is a closure"; };
$closure2 = function($param) {
	yield $param;
};

$rf1 = \BetterReflection\Reflection\ReflectionFunction::createFromName($closure1);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rf1->isGenerator());

$rf2 = \BetterReflection\Reflection\ReflectionFunction::createFromName($closure2);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rf2->isGenerator());

function func1() {
	return 'func1';
}

function func2() {
	yield 'func2';
}

$rf1 = \BetterReflection\Reflection\ReflectionFunction::createFromName('func1');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rf1->isGenerator());

$rf2 = \BetterReflection\Reflection\ReflectionFunction::createFromName('func2');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rf2->isGenerator());


class Foo {
	public function f1() {
	}

	public function f2() {
		yield;
	}
}

$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('Foo');
foreach($rc->getMethods() as $m) {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($m->isGenerator());
}
?>
--EXPECTF--
bool(false)
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
