--TEST--
Bug #60357 (__toString() method triggers E_NOTICE "Array to string conversion")
--FILE--
<?php require 'vendor/autoload.php';
function foo( array $x = array( 'a', 'b' ) ) {}
$r = \BetterReflection\Reflection\ReflectionParameter::createFromName( 'foo', 0 );
echo $r->__toString();
?>
--EXPECTF--
Parameter #0 [ <optional> array $x = Array ]
