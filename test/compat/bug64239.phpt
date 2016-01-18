--TEST--
Bug #64239 (ReflectionClass::getMethods() changed behavior)
--FILE--
<?php require 'vendor/autoload.php';
class A {
	use T2 { t2method as Bmethod; }
}
trait T2 {
	public function t2method() {
	}
}

class B extends A{
}

$obj = \BetterReflection\Reflection\ReflectionClass::createFromName("B");
print_r($obj->getMethods());
print_r(($method = $obj->getMethod("Bmethod")));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($method->getName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($method->getShortName());
?>
--EXPECT--
Array
(
    [0] => ReflectionMethod Object
        (
            [name] => Bmethod
            [class] => A
        )

    [1] => ReflectionMethod Object
        (
            [name] => t2method
            [class] => A
        )

)
ReflectionMethod Object
(
    [name] => Bmethod
    [class] => A
)
string(7) "Bmethod"
string(7) "Bmethod"
