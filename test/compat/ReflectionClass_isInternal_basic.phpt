--TEST--
ReflectionClass::isInternal()
--FILE--
<?php require 'vendor/autoload.php';
class C {
}

$r1 = \BetterReflection\Reflection\ReflectionClass::createFromName("stdClass");
$r2 = \BetterReflection\Reflection\ReflectionClass::createFromName("ReflectionClass");
$r3 = \BetterReflection\Reflection\ReflectionClass::createFromName("ReflectionProperty");
$r4 = \BetterReflection\Reflection\ReflectionClass::createFromName("Exception");
$r5 = \BetterReflection\Reflection\ReflectionClass::createFromName("C");

var_dump($r1->isInternal(), $r2->isInternal(), $r3->isInternal(), 
		 $r4->isInternal(), $r5->isInternal());
?>
--EXPECTF--
bool(true)
bool(true)
bool(true)
bool(true)
bool(false)
