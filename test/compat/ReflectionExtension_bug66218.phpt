--TEST--
ReflectionExtension::getFunctions() ##6218 zend_register_functions breaks reflection
--SKIPIF--
<?php
if (!extension_loaded('reflection')) print 'skip missing reflection extension';
if (PHP_SAPI != "cli") die("skip CLI only test");
if (!function_exists("dl")) die("skip need dl");
?>
--FILE--
<?php require 'vendor/autoload.php';
$r = \BetterReflection\Reflection\ReflectionExtension::createFromName('standard');
$t = $r->getFunctions();
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($t['dl']);
?>
Done
--EXPECTF--
object(ReflectionFunction)#%d (1) {
  ["name"]=>
  string(2) "dl"
}
Done
