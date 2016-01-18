--TEST--
ReflectionClass::hasProperty()
--CREDITS--
Marc Veldman <marc@ibuildings.nl>
#testfest roosendaal on 2008-05-10
--FILE-- 
<?php require 'vendor/autoload.php';
//New instance of class C - defined below
$rc = \BetterReflection\Reflection\ReflectionClass::createFromName("C");

//Check if C has public property publicFoo
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->hasProperty('publicFoo'));

//Check if C has protected property protectedFoo
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->hasProperty('protectedFoo'));

//Check if C has private property privateFoo
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->hasProperty('privateFoo'));

//Check if C has static property staticFoo
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->hasProperty('staticFoo'));

//C should not have property bar
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->hasProperty('bar'));

Class C {
  public $publicFoo;
  protected $protectedFoo;
  private $privateFoo;
  public static $staticFoo;
}
?>
--EXPECTF--
bool(true)
bool(true)
bool(true)
bool(true)
bool(false)
