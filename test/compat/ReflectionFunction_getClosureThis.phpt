--TEST--
Reflection::getClosureThis()
--SKIPIF--
<?php
if (!extension_loaded('reflection') || !defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300) {
  print 'skip';
}
?>
--FILE-- 
<?php require 'vendor/autoload.php';
$closure = function($param) { return "this is a closure"; };
$rf = \BetterReflection\Reflection\ReflectionFunction::createFromName($closure);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rf->getClosureThis());
echo "Done!\n";
--EXPECTF--
NULL
Done!
