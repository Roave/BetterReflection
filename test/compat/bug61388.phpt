--TEST--
ReflectionObject:getProperties() issues invalid reads when it get_properties returns a hash table with (inaccessible) dynamic numeric properties
--SKIPIF--
skip
<?php
// Skipping this as too slow currently :(
// see https://github.com/Roave/BetterReflection/issues/146
--FILE--
<?php require 'vendor/autoload.php';
$x = new ArrayObject();
$x[0] = 'test string 2';
$x['test'] = 'test string 3';
$reflObj = \BetterReflection\Reflection\ReflectionObject::createFromInstance($x);
print_r($reflObj->getProperties(ReflectionProperty::IS_PUBLIC));

$x = (object)array("a", "oo" => "b");
$reflObj = \BetterReflection\Reflection\ReflectionObject::createFromInstance($x);
print_r($reflObj->getProperties(ReflectionProperty::IS_PUBLIC));
--EXPECT--
Array
(
    [0] => ReflectionProperty Object
        (
            [name] => test
            [class] => ArrayObject
        )

)
Array
(
    [0] => ReflectionProperty Object
        (
            [name] => oo
            [class] => stdClass
        )

)
