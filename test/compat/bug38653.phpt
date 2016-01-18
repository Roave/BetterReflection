--TEST--
Bug #38653 (memory leak in ReflectionClass::getConstant())
--FILE--
<?php require 'vendor/autoload.php';

class foo {
	    const cons = 10;
	    const cons1 = "";
	    const cons2 = "test";
}

class bar extends foo {
}

$foo = \BetterReflection\Reflection\ReflectionClass::createFromName("foo");
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($foo->getConstant("cons"));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($foo->getConstant("cons1"));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($foo->getConstant("cons2"));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($foo->getConstant("no such const"));

echo "Done\n";
?>
--EXPECTF--	
int(10)
string(0) ""
string(4) "test"
bool(false)
Done
