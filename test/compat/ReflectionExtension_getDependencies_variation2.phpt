--TEST--
ReflectionExtension::getDependencies() method on an extension with one optional dependency
--CREDITS--
Felix De Vliegher <felix.devliegher@gmail.com>
--FILE--
<?php require 'vendor/autoload.php';
$standard = \BetterReflection\Reflection\ReflectionExtension::createFromName('standard');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($standard->getDependencies());
?>
==DONE==
--EXPECTF--
array(1) {
  ["session"]=>
  %s(8) "Optional"
}
==DONE==
