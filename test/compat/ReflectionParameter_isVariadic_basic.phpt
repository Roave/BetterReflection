--TEST--
ReflectionParameter::isVariadic()
--FILE--
<?php require 'vendor/autoload.php';

function test1($args) {}
function test2(...$args) {}
function test3($arg, ...$args) {}

$r1 = \BetterReflection\Reflection\ReflectionFunction::createFromName('test1');
$r2 = \BetterReflection\Reflection\ReflectionFunction::createFromName('test2');
$r3 = \BetterReflection\Reflection\ReflectionFunction::createFromName('test3');

var_dump($r1->getParameters()[0]->isVariadic());
var_dump($r2->getParameters()[0]->isVariadic());
var_dump($r3->getParameters()[0]->isVariadic());
var_dump($r3->getParameters()[1]->isVariadic());

?>
--EXPECT--
bool(false)
bool(true)
bool(false)
bool(true)
