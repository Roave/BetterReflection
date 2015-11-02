--TEST--
Bug #52854: ReflectionClass::newInstanceArgs does not work for classes without constructors
--FILE--
<?php require 'vendor/autoload.php';
class Test {
}
$c = \BetterReflection\Reflection\ReflectionClass::createFromName('Test');
var_dump(new Test);
var_dump(new Test());
var_dump($c->newInstance());
var_dump($c->newInstanceArgs(array()));

try {
	var_dump($c->newInstanceArgs(array(1)));
} catch(ReflectionException $e) {
	echo $e->getMessage()."\n";
}
?>
--EXPECTF--
object(Test)#%d (0) {
}
object(Test)#%d (0) {
}
object(Test)#%d (0) {
}
object(Test)#%d (0) {
}
Class Test does not have a constructor, so you cannot pass any constructor arguments
