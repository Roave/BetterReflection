--TEST--
Reflection on invokable objects
--FILE-- 
<?php require 'vendor/autoload.php';

class Test {
	function __invoke($a, $b = 0) { }
}

$rm = \BetterReflection\Reflection\ReflectionMethod::createFromName(new Test, '__invoke');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->getName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->getNumberOfParameters());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->getNumberOfRequiredParameters());

$rp = \BetterReflection\Reflection\ReflectionParameter::createFromName(array(new Test, '__invoke'), 0);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rp->isOptional());

$rp = \BetterReflection\Reflection\ReflectionParameter::createFromName(array(new Test, '__invoke'), 1);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rp->isOptional());

?>
===DONE===
--EXPECTF--
string(8) "__invoke"
int(2)
int(1)
bool(false)
bool(true)
===DONE===
