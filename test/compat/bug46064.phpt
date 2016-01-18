--TEST--
Bug #46064 (Exception when creating ReflectionProperty object on dynamicly created property)
--FILE--
<?php require 'vendor/autoload.php';

class x {
	public $zzz = 2;
}

$o = new x;
$o->z = 1000;
$o->zzz = 3;

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($h = \BetterReflection\Reflection\ReflectionProperty::createFromName($o, 'z'));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($h->isDefault());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($h->isPublic());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($h->isStatic());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($h->getName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(Reflection::getModifierNames($h->getModifiers()));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($h->getValue($o));

print "---------------------------\n";
try {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(\BetterReflection\Reflection\ReflectionProperty::createFromName($o, 'zz'));
} catch (Exception $e) {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($e->getMessage());
}

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(\BetterReflection\Reflection\ReflectionProperty::createFromName($o, 'zzz'));

class test {
	protected $a = 1;
}

class bar extends test {
	public function __construct() {
		$this->foobar = 2;
		$this->a = 200;
		
		$p = \BetterReflection\Reflection\ReflectionProperty::createFromName($this, 'foobar');
		// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($p->getValue($this), $p->isDefault(), $p->isPublic());
	}
}

new bar;

?>
===DONE===
--EXPECTF--
object(ReflectionProperty)#%d (2) {
  ["name"]=>
  string(1) "z"
  ["class"]=>
  string(1) "x"
}
bool(false)
bool(true)
bool(false)
string(1) "z"
array(1) {
  [0]=>
  string(6) "public"
}
int(1000)
---------------------------
string(30) "Property x::$zz does not exist"
object(ReflectionProperty)#%d (2) {
  ["name"]=>
  string(3) "zzz"
  ["class"]=>
  string(1) "x"
}
int(2)
bool(false)
bool(true)
===DONE===
