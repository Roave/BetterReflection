--TEST--
Bug #45139 (ReflectionProperty returns incorrect declaring class)
--FILE--
<?php require 'vendor/autoload.php';

class A {
	private $foo;
}

class B extends A {
	protected $bar;
	private $baz;
	private $quux;
}

class C extends B {
	public $foo;
	private $baz;
	protected $quux;
}

$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('C');
$rp = $rc->getProperty('foo');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rp->getDeclaringClass()->getName()); // c

$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('A');
$rp = $rc->getProperty('foo');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rp->getDeclaringClass()->getName()); // A

$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('B');
$rp = $rc->getProperty('bar');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rp->getDeclaringClass()->getName()); // B

$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('C');
$rp = $rc->getProperty('bar');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rp->getDeclaringClass()->getName()); // B

$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('C');
$rp = $rc->getProperty('baz');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rp->getDeclaringClass()->getName()); // C

$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('B');
$rp = $rc->getProperty('baz');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rp->getDeclaringClass()->getName()); // B

$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('C');
$rp = $rc->getProperty('quux');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rp->getDeclaringClass()->getName()); // C

?>
--EXPECT--
string(1) "C"
string(1) "A"
string(1) "B"
string(1) "B"
string(1) "C"
string(1) "B"
string(1) "C"
