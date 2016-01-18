--TEST--
Bug #49719 (ReflectionClass::hasProperty returns true for a private property in base class)
--FILE--
<?php require 'vendor/autoload.php';

class A {
	private $a;
}
class B extends A {
	private $b;
}

try {
	$b = new B;
	$ref = \BetterReflection\Reflection\ReflectionClass::createFromName($b);
	
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(property_exists('b', 'a'));
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(property_exists($b, 'a'));
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ref->hasProperty('a'));
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ref->getProperty('a'));
} catch (Exception $e) {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($e->getMessage());
}

class A2 {
	private $a = 1;
}

class B2 extends A2 {
	private $a = 2;
}

$b2 = \BetterReflection\Reflection\ReflectionClass::createFromName('B2');
$prop = $b2->getProperty('a');
$prop->setAccessible(true);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($prop->getValue(new b2));

?>
--EXPECTF--
bool(false)
bool(false)
bool(false)
%string|unicode%(25) "Property a does not exist"
int(2)
