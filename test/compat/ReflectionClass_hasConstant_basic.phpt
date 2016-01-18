--TEST--
ReflectionClass::hasConstant()
--CREDITS--
Marc Veldman <marc@ibuildings.nl>
#testfest roosendaal on 2008-05-10
--FILE-- 
<?php require 'vendor/autoload.php';
//New instance of class C - defined below
$rc = \BetterReflection\Reflection\ReflectionClass::createFromName("C");

//Check if C has constant foo
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->hasConstant('foo'));

//C should not have constant bar
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->hasConstant('bar'));

Class C {
  const foo=1;
}
?>
--EXPECTF--
bool(true)
bool(false)
