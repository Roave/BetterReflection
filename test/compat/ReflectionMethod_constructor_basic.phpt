--TEST--
ReflectionMethod::isConstructor()
--FILE--
<?php require 'vendor/autoload.php';

class NewCtor {
    function __construct() {
        echo "In " . __METHOD__ . "\n";
    }

}
echo "New-style constructor:\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName("NewCtor::__construct");
var_dump($methodInfo->isConstructor());

class ExtendsNewCtor extends NewCtor {
}
echo "\nInherited new-style constructor\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName("ExtendsNewCtor::__construct");
var_dump($methodInfo->isConstructor());

class OldCtor {
    function OldCtor() {
        echo "In " . __METHOD__ . "\n";
    }
}
echo "\nOld-style constructor:\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName("OldCtor::OldCtor");
var_dump($methodInfo->isConstructor());

class ExtendsOldCtor extends OldCtor {
}
echo "\nInherited old-style constructor:\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName("ExtendsOldCtor::OldCtor");
var_dump($methodInfo->isConstructor());

class X {
    function Y() {
        echo "In " . __METHOD__ . "\n";
    }
}
echo "\nNot a constructor:\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName("X::Y");
var_dump($methodInfo->isConstructor());

class Y extends X {
}
echo "\nInherited method of the same name as the class:\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName("Y::Y");
var_dump($methodInfo->isConstructor());

class OldAndNewCtor {
    function OldAndNewCtor() {
        echo "In " . __METHOD__ . "\n";
    }

    function __construct() {
        echo "In " . __METHOD__ . "\n";
    }
}
echo "\nOld-style constructor:\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName("OldAndNewCtor::OldAndNewCtor");
var_dump($methodInfo->isConstructor());

echo "\nRedefined constructor:\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName("OldAndNewCtor::__construct");
var_dump($methodInfo->isConstructor());

class NewAndOldCtor {
    function __construct() {
        echo "In " . __METHOD__ . "\n";
    }

    function NewAndOldCtor() {
        echo "In " . __METHOD__ . "\n";
    }
}
echo "\nNew-style constructor:\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName("NewAndOldCtor::__construct");
var_dump($methodInfo->isConstructor());

echo "\nRedefined old-style constructor:\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName("NewAndOldCtor::NewAndOldCtor");
var_dump($methodInfo->isConstructor());

?>
--EXPECTF--
Deprecated: Methods with the same name as their class will not be constructors in a future version of PHP; OldCtor has a deprecated constructor in %s on line %d
New-style constructor:
bool(true)

Inherited new-style constructor
bool(true)

Old-style constructor:
bool(true)

Inherited old-style constructor:
bool(true)

Not a constructor:
bool(false)

Inherited method of the same name as the class:
bool(false)

Old-style constructor:
bool(false)

Redefined constructor:
bool(true)

New-style constructor:
bool(true)

Redefined old-style constructor:
bool(false)
