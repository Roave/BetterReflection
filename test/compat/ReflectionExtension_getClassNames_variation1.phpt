--TEST--
ReflectionExtension::getClassNames() method on an extension with no classes
--CREDITS--
Felix De Vliegher <felix.devliegher@gmail.com>
--SKIPIF--
<?php
extension_loaded('ctype') or die("skip Requires 'ctype' extension");
?>
--FILE--
<?php require 'vendor/autoload.php';
$extension = \BetterReflection\Reflection\ReflectionExtension::createFromName('ctype');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($extension->getClassNames());
?>
==DONE==
--EXPECT--
array(0) {
}
==DONE==
