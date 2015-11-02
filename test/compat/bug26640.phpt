--TEST--
Reflection Bug #26640 (__autoload() not invoked by Reflection classes)
--FILE--
<?php require 'vendor/autoload.php';

function __autoload($c)
{
	class autoload_class
	{
		public function __construct()
		{
			print "autoload success\n";
		}
	}
}

$a = \BetterReflection\Reflection\ReflectionClass::createFromName('autoload_class');

if (is_object($a)) {
	echo "OK\n";
}

?>
--EXPECT--
OK
