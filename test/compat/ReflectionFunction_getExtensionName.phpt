--TEST--
ReflectionFunction::getExtensionName()
--FILE--
<?php require 'vendor/autoload.php';
function foo() {}

$function = \BetterReflection\Reflection\ReflectionFunction::createFromName('sort');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getExtensionName());

$function = \BetterReflection\Reflection\ReflectionFunction::createFromName('foo');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getExtensionName());
?>
--EXPECT--
string(8) "standard"
bool(false)

