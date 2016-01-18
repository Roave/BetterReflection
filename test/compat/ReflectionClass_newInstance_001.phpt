--TEST--
ReflectionClass::newInstance()
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--FILE--
<?php require 'vendor/autoload.php';
class A {
	public function A() {
		echo "In constructor of class A\n"; 
	}	
}

class B {
	public function __construct($a, $b) {
		echo "In constructor of class B with args $a, $b\n";
	}
}

class C {
	protected function __construct() {
		echo "In constructor of class C\n"; 
	}
}

class D {
	private function __construct() {
		echo "In constructor of class D\n";		
	}
}
class E {	
}


$rcA = \BetterReflection\Reflection\ReflectionClass::createFromName('A');
$rcB = \BetterReflection\Reflection\ReflectionClass::createFromName('B');
$rcC = \BetterReflection\Reflection\ReflectionClass::createFromName('C');
$rcD = \BetterReflection\Reflection\ReflectionClass::createFromName('D');
$rcE = \BetterReflection\Reflection\ReflectionClass::createFromName('E');

$a1 = $rcA->newInstance();
$a2 = $rcA->newInstance('x');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($a1, $a2);

$b1 = $rcB->newInstance();
$b2 = $rcB->newInstance('x', 123);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($b1, $b2);

try {
	$rcC->newInstance();
	echo "you should not see this\n";
} catch (Exception $e) {
	echo $e->getMessage() . "\n";
}

try {
	$rcD->newInstance();
	echo "you should not see this\n";
} catch (Exception $e) {
	echo $e->getMessage() . "\n";
}

$e1 = $rcE->newInstance();
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($e1);

try {
	$e2 = $rcE->newInstance('x');
	echo "you should not see this\n";
} catch (Exception $e) {
	echo $e->getMessage() . "\n";
}
?>
--EXPECTF--
Deprecated: Methods with the same name as their class will not be constructors in a future version of PHP; A has a deprecated constructor in %s on line %d
In constructor of class A
In constructor of class A
object(A)#%d (0) {
}
object(A)#%d (0) {
}

Warning: Missing argument 1 for B::__construct() in %s on line 9

Warning: Missing argument 2 for B::__construct() in %s on line 9

Notice: Undefined variable: a in %s on line 10

Notice: Undefined variable: b in %s on line 10
In constructor of class B with args , 
In constructor of class B with args x, 123
object(B)#%d (0) {
}
object(B)#%d (0) {
}
Access to non-public constructor of class C
Access to non-public constructor of class D
object(E)#%d (0) {
}
Class E does not have a constructor, so you cannot pass any constructor arguments
