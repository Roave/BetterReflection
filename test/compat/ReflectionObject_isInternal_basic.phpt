--TEST--
ReflectionObject::isInternal() - basic function test
--FILE--
<?php require 'vendor/autoload.php';
class C {
}

$r1 = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new stdClass);
$r2 = \BetterReflection\Reflection\ReflectionObject::createFromInstance(\BetterReflection\Reflection\ReflectionClass::createFromName('C'));
$r3 = \BetterReflection\Reflection\ReflectionObject::createFromInstance(\BetterReflection\Reflection\ReflectionProperty::createFromName('Exception', 'message'));
$r4 = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new Exception);
$r5 = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new C);

var_dump($r1->isInternal(), $r2->isInternal(), $r3->isInternal(), 
		 $r4->isInternal(), $r5->isInternal());

?>
--EXPECTF--
bool(true)
bool(true)
bool(true)
bool(true)
bool(false)
