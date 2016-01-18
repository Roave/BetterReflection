--TEST--
Test ReflectionProperty::export() usage.
--FILE--
<?php require 'vendor/autoload.php';

class TestClass {
    public $proper = 5;
}

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(ReflectionProperty::export('TestClass', 'proper'));

?>
--EXPECT--
Property [ <default> public $proper ]

NULL
