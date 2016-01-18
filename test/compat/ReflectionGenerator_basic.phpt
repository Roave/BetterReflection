--TEST--
ReflectionGenerator basic test
--FILE--
<?php require 'vendor/autoload.php';

function foo() {
	yield;
}

$gens = [
	(new class() {
		function a() {
			yield from foo();
		}
	})->a(),
	(function() {
		yield;
	})(),
	foo(),
];

foreach ($gens as $gen) {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($gen);

	$gen->valid(); // start Generator
	$ref = \BetterReflection\Reflection\ReflectionGenerator::createFromName($gen);

	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ref->getTrace());
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ref->getExecutingLine());
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ref->getExecutingFile());
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ref->getExecutingGenerator());
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ref->getFunction());
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ref->getThis());
}

?>
--EXPECTF--
object(Generator)#2 (0) {
}
array(1) {
  [0]=>
  array(2) {
    ["function"]=>
    string(3) "foo"
    ["args"]=>
    array(0) {
    }
  }
}
int(%d)
string(%d) "%sReflectionGenerator_basic.%s"
object(Generator)#6 (0) {
}
object(ReflectionMethod)#8 (2) {
  ["name"]=>
  string(1) "a"
  ["class"]=>
  string(%d) "class@anonymous%s"
}
object(class@anonymous)#1 (0) {
}
object(Generator)#4 (0) {
}
array(0) {
}
int(%d)
string(%d) "%sReflectionGenerator_basic.%s"
object(Generator)#4 (0) {
}
object(ReflectionFunction)#7 (1) {
  ["name"]=>
  string(9) "{closure}"
}
NULL
object(Generator)#5 (0) {
}
array(0) {
}
int(%d)
string(%d) "%sReflectionGenerator_basic.%s"
object(Generator)#5 (0) {
}
object(ReflectionFunction)#8 (1) {
  ["name"]=>
  string(3) "foo"
}
NULL
