--TEST--
ReflectionClass::isUserDefined()
--FILE--
<?php require 'vendor/autoload.php';
class C {
}

$r1 = \BetterReflection\Reflection\ReflectionClass::createFromName("stdClass");
$r2 = \BetterReflection\Reflection\ReflectionClass::createFromName("ReflectionClass");
$r3 = \BetterReflection\Reflection\ReflectionClass::createFromName("ReflectionProperty");
$r4 = \BetterReflection\Reflection\ReflectionClass::createFromName("Exception");
$r5 = \BetterReflection\Reflection\ReflectionClass::createFromName("C");

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r1->isUserDefined(), $r2->isUserDefined(), $r3->isUserDefined(),
		 $r4->isUserDefined(), $r5->isUserDefined());
?>
--EXPECTF--
bool(false)
bool(false)
bool(false)
bool(false)
bool(true)
