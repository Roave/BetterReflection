--TEST--
ReflectionClass::__toString() (constants)
--SKIPIF--
<?php extension_loaded('reflection') or die('skip'); ?>
--FILE--
<?php require 'vendor/autoload.php';
class Foo {
	const test = "ok";
}
$class = \BetterReflection\Reflection\ReflectionClass::createFromName("Foo");
echo $class;
?>
--EXPECTF--	
Class [ <user> class Foo ] {
  @@ %s017.php 2-4

  - Constants [1] {
    Constant [ string test ] { ok }
  }

  - Static properties [0] {
  }

  - Static methods [0] {
  }

  - Properties [0] {
  }

  - Methods [0] {
  }
}

