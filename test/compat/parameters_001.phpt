--TEST--
ReflectionParameter Check for parameter being optional
--FILE--
<?php require 'vendor/autoload.php';

class Test {
	function func($x, $y = NULL){
	}
}


$f = \BetterReflection\Reflection\ReflectionMethod::createFromName('Test', 'func');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($f->getNumberOfParameters());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($f->getNumberOfRequiredParameters());

$p = \BetterReflection\Reflection\ReflectionParameter::createFromName(array('Test', 'func'), 'x');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($p->isOptional());

$p = \BetterReflection\Reflection\ReflectionParameter::createFromName(array('Test', 'func'), 'y');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($p->isOptional());

try {
	$p = \BetterReflection\Reflection\ReflectionParameter::createFromName(array('Test', 'func'), 'z');
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($p->isOptional());
}
catch (Exception $e) {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($e->getMessage());
}

?>
===DONE===
--EXPECT--
int(2)
int(1)
bool(false)
bool(true)
string(54) "The parameter specified by its name could not be found"
===DONE===
