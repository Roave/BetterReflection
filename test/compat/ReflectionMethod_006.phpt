--TEST--
ReflectionMethod methods - wrong num args
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--FILE--
<?php require 'vendor/autoload.php';

try {
	\BetterReflection\Reflection\ReflectionMethod::createFromName();
} catch (TypeError $re) {
	echo "Ok - ".$re->getMessage().PHP_EOL;
}
try {
	\BetterReflection\Reflection\ReflectionMethod::createFromName('a', 'b', 'c');
} catch (TypeError $re) {
	echo "Ok - ".$re->getMessage().PHP_EOL;
}

class C {
    public function f() {}
}

$rm = \BetterReflection\Reflection\ReflectionMethod::createFromName('C', 'f');

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->isFinal(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->isAbstract(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->isPrivate(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->isProtected(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->isPublic(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->isStatic(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->isConstructor(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->isDestructor(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->getModifiers(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->isInternal(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->isUserDefined(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->getFileName(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->getStartLine(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->getEndLine(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->getStaticVariables(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->getName(1));


?>
--EXPECTF--
Ok - ReflectionMethod::__construct() expects exactly 1 parameter, 0 given
Ok - ReflectionMethod::__construct() expects exactly 1 parameter, 3 given

Warning: ReflectionMethod::isFinal() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionMethod::isAbstract() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionMethod::isPrivate() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionMethod::isProtected() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionMethod::isPublic() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionMethod::isStatic() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionMethod::isConstructor() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionMethod::isDestructor() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionMethod::getModifiers() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionFunctionAbstract::isInternal() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionFunctionAbstract::isUserDefined() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionFunctionAbstract::getFileName() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionFunctionAbstract::getStartLine() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionFunctionAbstract::getEndLine() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionFunctionAbstract::getStaticVariables() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionFunctionAbstract::getName() expects exactly 0 parameters, 1 given in %s on line %d
NULL
