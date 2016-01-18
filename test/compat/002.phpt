--TEST--
Reflection properties are read only
--FILE--
<?php require 'vendor/autoload.php';

class ReflectionMethodEx extends \BetterReflection\Reflection\ReflectionMethod
{
	public $foo = "xyz";
	
	function __construct($c,$m)
	{
		echo __METHOD__ . "\n";
		parent::__construct($c,$m);
	}
}

$r = \BetterReflection\Reflection\ReflectionMethodEx::createFromName('ReflectionMethodEx','getName');

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r->class);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r->name);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r->foo);
@// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r->bar);

try
{
	$r->class = 'bullshit';
}
catch(ReflectionException $e)
{
	echo $e->getMessage() . "\n";
}
try
{
$r->name = 'bullshit';
}
catch(ReflectionException $e)
{
	echo $e->getMessage() . "\n";
}

$r->foo = 'bar';
$r->bar = 'baz';

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r->class);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r->name);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r->foo);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r->bar);

?>
===DONE===
--EXPECTF--
ReflectionMethodEx::__construct
%unicode|string%(26) "ReflectionFunctionAbstract"
%unicode|string%(7) "getName"
%unicode|string%(3) "xyz"
NULL
Cannot set read-only property ReflectionMethodEx::$class
Cannot set read-only property ReflectionMethodEx::$name
%unicode|string%(26) "ReflectionFunctionAbstract"
%unicode|string%(7) "getName"
%unicode|string%(3) "bar"
%unicode|string%(3) "baz"
===DONE===
