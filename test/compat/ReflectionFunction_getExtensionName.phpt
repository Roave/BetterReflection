--TEST--
ReflectionFunction::getExtensionName()
--FILE--
<?php require 'vendor/autoload.php';
function foo() {}

$function = \BetterReflection\Reflection\ReflectionFunction::createFromName('sort');
var_dump($function->getExtensionName());

$function = \BetterReflection\Reflection\ReflectionFunction::createFromName('foo');
var_dump($function->getExtensionName());
?>
--EXPECT--
string(8) "standard"
bool(false)

