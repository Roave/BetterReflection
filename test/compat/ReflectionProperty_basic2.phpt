--TEST--
Test usage of ReflectionProperty methods isDefault(), getModifiers(), getDeclaringClass() and getDocComment().
--INI--
opcache.save_comments=1
--FILE--
<?php require 'vendor/autoload.php';

function reflectProperty($class, $property) {
    $propInfo = \BetterReflection\Reflection\ReflectionProperty::createFromName($class, $property);
    echo "**********************************\n";
    echo "Reflecting on property $class::$property\n\n";
    echo "isDefault():\n";
    // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($propInfo->isDefault());
    echo "getModifiers():\n";
    // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($propInfo->getModifiers());
    echo "getDeclaringClass():\n";
    // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($propInfo->getDeclaringClass());
    echo "getDocComment():\n";
    // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($propInfo->getDocComment());
    echo "\n**********************************\n";
}

class TestClass {
    public $pub;
    static public $stat = "static property";
    /**
     * This property has a comment.
     */
    protected $prot = 4;
    private $priv = "keepOut";
}

reflectProperty("TestClass", "pub");
reflectProperty("TestClass", "stat");
reflectProperty("TestClass", "prot");
reflectProperty("TestClass", "priv");

?> 
--EXPECTF--
**********************************
Reflecting on property TestClass::pub

isDefault():
bool(true)
getModifiers():
int(256)
getDeclaringClass():
object(ReflectionClass)#%d (1) {
  ["name"]=>
  string(9) "TestClass"
}
getDocComment():
bool(false)

**********************************
**********************************
Reflecting on property TestClass::stat

isDefault():
bool(true)
getModifiers():
int(257)
getDeclaringClass():
object(ReflectionClass)#%d (1) {
  ["name"]=>
  string(9) "TestClass"
}
getDocComment():
bool(false)

**********************************
**********************************
Reflecting on property TestClass::prot

isDefault():
bool(true)
getModifiers():
int(512)
getDeclaringClass():
object(ReflectionClass)#%d (1) {
  ["name"]=>
  string(9) "TestClass"
}
getDocComment():
string(%d) "/**
     * This property has a comment.
     */"

**********************************
**********************************
Reflecting on property TestClass::priv

isDefault():
bool(true)
getModifiers():
int(1024)
getDeclaringClass():
object(ReflectionClass)#%d (1) {
  ["name"]=>
  string(9) "TestClass"
}
getDocComment():
bool(false)

**********************************
