--TEST--
ReflectionClass::hasConstant()
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--FILE--
<?php require 'vendor/autoload.php';
class C {
	const myConst = 1;
}

class D extends C {
}


$rc = \BetterReflection\Reflection\ReflectionClass::createFromName("C");
echo "Check existing constant: ";
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->hasConstant("myConst"));
echo "Check existing constant, different case: ";
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->hasConstant("MyCoNsT"));
echo "Check absent constant: ";
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->hasConstant("doesntExist"));


$rd = \BetterReflection\Reflection\ReflectionClass::createFromName("D");
echo "Check inherited constant: ";
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rd->hasConstant("myConst"));
echo "Check absent constant: ";
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rd->hasConstant("doesntExist"));
?>
--EXPECTF--
Check existing constant: bool(true)
Check existing constant, different case: bool(false)
Check absent constant: bool(false)
Check inherited constant: bool(true)
Check absent constant: bool(false)
