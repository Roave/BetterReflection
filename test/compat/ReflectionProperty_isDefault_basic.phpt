--TEST--
Test ReflectionProperty::isDefault() usage.
--FILE--
<?php require 'vendor/autoload.php';

function reflectProperty($class, $property) {
    $propInfo = \BetterReflection\Reflection\ReflectionProperty::createFromName($class, $property);
    echo "**********************************\n";
    echo "Reflecting on property $class::$property\n\n";
    echo "isDefault():\n";
    // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($propInfo->isDefault());
    echo "\n**********************************\n";
}

class TestClass {
    public $pub;
    static public $stat = "static property";
    protected $prot = 4;
    private $priv = "keepOut";
}

reflectProperty("TestClass", "pub");
reflectProperty("TestClass", "stat");
reflectProperty("TestClass", "prot");
reflectProperty("TestClass", "priv");

echo "Wrong number of params:\n";
$propInfo = \BetterReflection\Reflection\ReflectionProperty::createFromName('TestClass', 'pub');
$propInfo->isDefault(1);

?> 
--EXPECTF--
**********************************
Reflecting on property TestClass::pub

isDefault():
bool(true)

**********************************
**********************************
Reflecting on property TestClass::stat

isDefault():
bool(true)

**********************************
**********************************
Reflecting on property TestClass::prot

isDefault():
bool(true)

**********************************
**********************************
Reflecting on property TestClass::priv

isDefault():
bool(true)

**********************************
Wrong number of params:

Warning: ReflectionProperty::isDefault() expects exactly 0 parameters, 1 given in %s on line %d
