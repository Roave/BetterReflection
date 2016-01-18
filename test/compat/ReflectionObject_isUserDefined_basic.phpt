--TEST--
ReflectionObject::isUserDefined() - basic function test
--FILE--
<?php require 'vendor/autoload.php';
class C {
}

$r1 = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new stdClass);
$r2 = \BetterReflection\Reflection\ReflectionObject::createFromInstance(\BetterReflection\Reflection\ReflectionClass::createFromName('C'));
$r3 = \BetterReflection\Reflection\ReflectionObject::createFromInstance(\BetterReflection\Reflection\ReflectionProperty::createFromName('Exception', 'message'));
$r4 = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new Exception);
$r5 = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new C);

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r1->isUserDefined(), $r2->isUserDefined(), $r3->isUserDefined(),
		 $r4->isUserDefined(), $r5->isUserDefined());

?>
--EXPECTF--
bool(false)
bool(false)
bool(false)
bool(false)
bool(true)
