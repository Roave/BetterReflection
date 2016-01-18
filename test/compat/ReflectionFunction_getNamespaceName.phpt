--TEST--
ReflectionFunction::getNamespaceName()
--FILE--
<?php require 'vendor/autoload.php';
namespace A\B;
function foo() {}

$function = new \ReflectionFunction('sort');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->inNamespace());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getNamespaceName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getShortName());

$function = new \ReflectionFunction('A\\B\\foo');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->inNamespace());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getNamespaceName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getShortName());
?>
--EXPECT--
bool(false)
string(4) "sort"
string(0) ""
string(4) "sort"
bool(true)
string(7) "A\B\foo"
string(3) "A\B"
string(3) "foo"

