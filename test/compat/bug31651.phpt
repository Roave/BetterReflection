--TEST--
Reflection Bug #31651 (ReflectionClass::getDefaultProperties segfaults with arrays.)
--FILE--
<?php require 'vendor/autoload.php';

class Test
{
	public $a = array('a' => 1);
}

$ref = \BetterReflection\Reflection\ReflectionClass::createFromName('Test');

print_r($ref->getDefaultProperties());

?>
--EXPECT--
Array
(
    [a] => Array
        (
            [a] => 1
        )

)
