--TEST--
ReflectionFunction::getExtension()
--FILE--
<?php require 'vendor/autoload.php';
function foo () {}

$function = \BetterReflection\Reflection\ReflectionFunction::createFromName('sort');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getExtension());

$function = \BetterReflection\Reflection\ReflectionFunction::createFromName('foo');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getExtension());
?>
--EXPECTF--
object(ReflectionExtension)#%i (1) {
  ["name"]=>
  string(8) "standard"
}
NULL

