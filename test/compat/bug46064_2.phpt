--TEST--
Bug #46064.2 (Exception when creating ReflectionProperty object on dynamicly created property)
--SKIPIF--
skip
<?php
// Skipping this as too slow currently :(
// see https://github.com/Roave/BetterReflection/issues/146
--FILE--
<?php require 'vendor/autoload.php';

class foo { 
}

$x = new foo;
$x->test = 2000;


$p = \BetterReflection\Reflection\ReflectionObject::createFromInstance($x);
var_dump($p->getProperty('test'));


class bar {
	public function __construct() {
		$this->a = 1;
	}
}

class test extends bar {
	private $b = 2;

	public function __construct() {
		parent::__construct();
		
		$p = \BetterReflection\Reflection\ReflectionObject::createFromInstance($this);
		var_dump($h = $p->getProperty('a'));
		var_dump($h->isDefault(), $h->isProtected(), $h->isPrivate(), $h->isPublic(), $h->isStatic());
		var_dump($p->getProperties());
	}
}

new test;

?>
===DONE===
--EXPECTF--
object(ReflectionProperty)#%d (2) {
  ["name"]=>
  string(4) "test"
  ["class"]=>
  string(3) "foo"
}
object(ReflectionProperty)#%d (2) {
  ["name"]=>
  string(1) "a"
  ["class"]=>
  string(4) "test"
}
bool(false)
bool(false)
bool(false)
bool(true)
bool(false)
array(2) {
  [0]=>
  object(ReflectionProperty)#%d (2) {
    ["name"]=>
    string(1) "b"
    ["class"]=>
    string(4) "test"
  }
  [1]=>
  object(ReflectionProperty)#%d (2) {
    ["name"]=>
    string(1) "a"
    ["class"]=>
    string(4) "test"
  }
}
===DONE===
