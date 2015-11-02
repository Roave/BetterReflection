--TEST--
Bug #51911 (ReflectionParameter::getDefaultValue() memory leaks with constant array)
--FILE--
<?php require 'vendor/autoload.php';

class Foo {
   const X = 1;
   public function x($x = array(1)) {}
}

$clazz = \BetterReflection\Reflection\ReflectionClass::createFromName('Foo');
$method = $clazz->getMethod('x');
foreach ($method->getParameters() as $param) {
    if ( $param->isDefaultValueAvailable())
        echo '$', $param->getName(), ' : ', var_export($param->getDefaultValue(), 1), "\n";
}

?>
--EXPECT--
$x : array (
  0 => 1,
)
