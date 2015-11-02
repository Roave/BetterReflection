--TEST--
ReflectionFunction::getExtension()
--FILE--
<?php require 'vendor/autoload.php';
function foo () {}

$function = \BetterReflection\Reflection\ReflectionFunction::createFromName('sort');
var_dump($function->getExtension());

$function = \BetterReflection\Reflection\ReflectionFunction::createFromName('foo');
var_dump($function->getExtension());
?>
--EXPECTF--
object(ReflectionExtension)#%i (1) {
  ["name"]=>
  string(8) "standard"
}
NULL

