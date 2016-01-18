--TEST--
ReflectionClass::getNamespaceName()
--FILE--
<?php require 'vendor/autoload.php';
namespace A\B;
class Foo {
}

$function = new \ReflectionClass('stdClass');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->inNamespace());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getNamespaceName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getShortName());

$function = new \ReflectionClass('A\\B\\Foo');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->inNamespace());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getNamespaceName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($function->getShortName());
?>
--EXPECT--
bool(false)
string(8) "stdClass"
string(0) ""
string(8) "stdClass"
bool(true)
string(7) "A\B\Foo"
string(3) "A\B"
string(3) "Foo"

