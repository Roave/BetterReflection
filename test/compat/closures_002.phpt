--TEST--
Reflection on invokable objects
--FILE-- 
<?php require 'vendor/autoload.php';

class Test {
	function __invoke($a, $b = 0) { }
}

$rm = \BetterReflection\Reflection\ReflectionMethod::createFromName(new Test, '__invoke');
var_dump($rm->getName());
var_dump($rm->getNumberOfParameters());
var_dump($rm->getNumberOfRequiredParameters());

$rp = \BetterReflection\Reflection\ReflectionParameter::createFromName(array(new Test, '__invoke'), 0);
var_dump($rp->isOptional());

$rp = \BetterReflection\Reflection\ReflectionParameter::createFromName(array(new Test, '__invoke'), 1);
var_dump($rp->isOptional());

?>
===DONE===
--EXPECTF--
string(8) "__invoke"
int(2)
int(1)
bool(false)
bool(true)
===DONE===
