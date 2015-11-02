--TEST--
ReflectionProperty::__construct(): ensure inherited private props can't be accessed through ReflectionProperty.
--FILE--
<?php require 'vendor/autoload.php';

class C {
	private $p = 1;
	
	static function testFromC() {
		try {
		  $rp = \BetterReflection\Reflection\ReflectionProperty::createFromName("D", "p");
		  var_dump($rp);
		} catch (Exception $e) {
			echo $e->getMessage();
		}		
	}
}

class D extends C{
	static function testFromD() {
		try {
		  $rp = \BetterReflection\Reflection\ReflectionProperty::createFromName("D", "p");
		  var_dump($rp);
		} catch (Exception $e) {
			echo $e->getMessage();
		}		
	}
}

echo "--> Reflect inherited private from global scope:\n";
try {
  $rp = \BetterReflection\Reflection\ReflectionProperty::createFromName("D", "p");
  var_dump($rp);
} catch (Exception $e) {
	echo $e->getMessage();
}

echo "\n\n--> Reflect inherited private from declaring scope:\n";
C::testFromC();

echo "\n\n--> Reflect inherited private from declaring scope via subclass:\n";
D::testFromC();

echo "\n\n--> Reflect inherited private from subclass:\n";
D::testFromD();
?>
--EXPECTF--
--> Reflect inherited private from global scope:
Property D::$p does not exist

--> Reflect inherited private from declaring scope:
Property D::$p does not exist

--> Reflect inherited private from declaring scope via subclass:
Property D::$p does not exist

--> Reflect inherited private from subclass:
Property D::$p does not exist
