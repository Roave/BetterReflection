--TEST--
ReflectionParameter::isDefault()
--FILE--
<?php require 'vendor/autoload.php';
class A {
public $defprop;
}
$a = new A;
$a->myprop = null;

$ro = \BetterReflection\Reflection\ReflectionObject::createFromInstance($a);
$props = $ro->getProperties();
$prop1 = $props[0];
var_dump($prop1->isDefault());
$prop2 = $props[1];
var_dump($prop2->isDefault());

var_dump($ro->getProperty('defprop')->isDefault());
var_dump($ro->getProperty('myprop')->isDefault());

$prop1 = \BetterReflection\Reflection\ReflectionProperty::createFromName($a, 'defprop');
$prop2 = \BetterReflection\Reflection\ReflectionProperty::createFromName($a, 'myprop');
var_dump($prop1->isDefault());
var_dump($prop2->isDefault());
?>
==DONE==
--EXPECT--
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
bool(false)
==DONE==
