--TEST--
Modifiers
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--FILE--
<?php require 'vendor/autoload.php';
abstract class A {}
class B extends A {}
class C {}
final class D {}
interface I {}

$classes = array("A", "B", "C", "D", "I");

foreach ($classes as $class) {
	$rc = \BetterReflection\Reflection\ReflectionClass::createFromName($class);
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->isFinal());
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->isInterface());
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->isAbstract());
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getModifiers());
}
?>
--EXPECTF--
bool(false)
bool(false)
bool(true)
int(32)
bool(false)
bool(false)
bool(false)
int(0)
bool(false)
bool(false)
bool(false)
int(0)
bool(true)
bool(false)
bool(false)
int(4)
bool(false)
bool(true)
bool(false)
int(64)
