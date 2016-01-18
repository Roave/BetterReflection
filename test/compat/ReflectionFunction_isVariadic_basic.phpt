--TEST--
ReflectionFunction::isVariadic()
--FILE--
<?php require 'vendor/autoload.php';

function test1($args) {}
function test2(...$args) {}
function test3($arg, ...$args) {}

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump((\BetterReflection\Reflection\ReflectionFunction::createFromName('test1'))->isVariadic());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump((\BetterReflection\Reflection\ReflectionFunction::createFromName('test2'))->isVariadic());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump((\BetterReflection\Reflection\ReflectionFunction::createFromName('test3'))->isVariadic());

?>
--EXPECT--
bool(false)
bool(true)
bool(true)
