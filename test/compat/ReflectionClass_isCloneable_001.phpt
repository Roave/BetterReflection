--TEST--
Testing ReflectionClass::isCloneable()
--SKIPIF--
<?php if (!extension_loaded('simplexml') || !extension_loaded('xmlwriter')) die("skip SimpleXML and XMLWriter is required for this test"); ?>
--FILE--
<?php require 'vendor/autoload.php';

class foo {
}
$foo = new foo;

print "User class\n";
$obj = \BetterReflection\Reflection\ReflectionClass::createFromName($foo);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isCloneable());
$obj = \BetterReflection\Reflection\ReflectionObject::createFromInstance($foo);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isCloneable());
$h = clone $foo;

class bar {
	private function __clone() {
	}
}
$bar = new bar;
print "User class - private __clone\n";
$obj = \BetterReflection\Reflection\ReflectionClass::createFromName($bar);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isCloneable());
$obj = \BetterReflection\Reflection\ReflectionObject::createFromInstance($bar);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isCloneable());
$h = clone $foo;

print "Closure\n";
$closure = function () { };
$obj = \BetterReflection\Reflection\ReflectionClass::createFromName($closure);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isCloneable());
$obj = \BetterReflection\Reflection\ReflectionObject::createFromInstance($closure);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isCloneable());
$h = clone $closure;

print "Internal class - SimpleXMLElement\n";
$obj = \BetterReflection\Reflection\ReflectionClass::createFromName('simplexmlelement');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isCloneable());
$obj = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new simplexmlelement('<test></test>'));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isCloneable());
$h = clone new simplexmlelement('<test></test>');

print "Internal class - XMLWriter\n";
$obj = \BetterReflection\Reflection\ReflectionClass::createFromName('xmlwriter');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isCloneable());
$obj = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new XMLWriter);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isCloneable());
$h = clone new xmlwriter;

?>
--EXPECTF--
User class
bool(true)
bool(true)
User class - private __clone
bool(false)
bool(false)
Closure
bool(true)
bool(true)
Internal class - SimpleXMLElement
bool(true)
bool(true)
Internal class - XMLWriter
bool(false)
bool(false)

Fatal error: Uncaught Error: Trying to clone an uncloneable object of class XMLWriter in %s:%d
Stack trace:
#0 {main}
  thrown in %s on line %d
