--TEST--
Test ReflectionProperty::getDocComment() errors.
--FILE--
<?php require 'vendor/autoload.php';

class C {
    public $a;
}

$rc = \BetterReflection\Reflection\ReflectionProperty::createFromName('C', 'a');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getDocComment(null));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getDocComment('X'));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getDocComment(true));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getDocComment(array(1, 2, 3)));

?>
--EXPECTF--
Warning: ReflectionProperty::getDocComment() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionProperty::getDocComment() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionProperty::getDocComment() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionProperty::getDocComment() expects exactly 0 parameters, 1 given in %s on line %d
NULL
