--TEST--
ReflectionObject::IsInstantiable() - basic function test
--FILE--
<?php require 'vendor/autoload.php';
class C {
}

interface iface {
	function f1();
}

class ifaceImpl implements iface {
	function f1() {}
}

abstract class abstractClass {
	function f1() {}
	abstract function f2();
}

class D extends abstractClass {
	function f2() {}
}

$classes = array("C", "ifaceImpl", "D");

foreach($classes  as $class ) {
	$ro = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new $class);
	echo "Is $class instantiable?  ";
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ro->IsInstantiable());
}
?>
--EXPECTF--
Is C instantiable?  bool(true)
Is ifaceImpl instantiable?  bool(true)
Is D instantiable?  bool(true)
