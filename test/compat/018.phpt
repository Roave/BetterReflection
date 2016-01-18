--TEST--
Reflection::getModifierNames
--SKIPIF--
<?php extension_loaded('reflection') or die('skip'); ?>
--FILE--
<?php require 'vendor/autoload.php';
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(Reflection::getModifierNames(ReflectionMethod::IS_FINAL | ReflectionMethod::IS_PROTECTED));
?>
--EXPECT--	
array(2) {
  [0]=>
  string(5) "final"
  [1]=>
  string(9) "protected"
}
