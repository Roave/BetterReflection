--TEST--
ReflectionParameter Check for parameter being optional
--FILE--
<?php require 'vendor/autoload.php';

class Test {
	function func($x, $y = NULL){
	}
}


$f = \BetterReflection\Reflection\ReflectionMethod::createFromName('Test', 'func');
var_dump($f->getNumberOfParameters());
var_dump($f->getNumberOfRequiredParameters());

$p = \BetterReflection\Reflection\ReflectionParameter::createFromName(array('Test', 'func'), 'x');
var_dump($p->isOptional());

$p = \BetterReflection\Reflection\ReflectionParameter::createFromName(array('Test', 'func'), 'y');
var_dump($p->isOptional());

try {
	$p = \BetterReflection\Reflection\ReflectionParameter::createFromName(array('Test', 'func'), 'z');
	var_dump($p->isOptional());
}
catch (Exception $e) {
	var_dump($e->getMessage());
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
