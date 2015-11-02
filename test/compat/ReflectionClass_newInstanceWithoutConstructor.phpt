--TEST--
ReflectionClass::newInstanceWithoutConstructor()
--CREDITS--
Sebastian Bergmann <sebastian@php.net>
--FILE--
<?php require 'vendor/autoload.php';
class Foo
{
    public function __construct()
    {
        print __METHOD__;
    }
}

$class = \BetterReflection\Reflection\ReflectionClass::createFromName('Foo');
var_dump($class->newInstanceWithoutConstructor());

$class = \BetterReflection\Reflection\ReflectionClass::createFromName('StdClass');
var_dump($class->newInstanceWithoutConstructor());

$class = \BetterReflection\Reflection\ReflectionClass::createFromName('DateTime');
var_dump($class->newInstanceWithoutConstructor());

$class = \BetterReflection\Reflection\ReflectionClass::createFromName('Generator');
var_dump($class->newInstanceWithoutConstructor());
--EXPECTF--
object(Foo)#%d (0) {
}
object(stdClass)#%d (0) {
}
object(DateTime)#%d (0) {
}

Fatal error: Uncaught ReflectionException: Class Generator is an internal class marked as final that cannot be instantiated without invoking its constructor in %sReflectionClass_newInstanceWithoutConstructor.php:%d
Stack trace:
#0 %sReflectionClass_newInstanceWithoutConstructor.php(%d): ReflectionClass->newInstanceWithoutConstructor()
#1 {main}
  thrown in %sReflectionClass_newInstanceWithoutConstructor.php on line %d
