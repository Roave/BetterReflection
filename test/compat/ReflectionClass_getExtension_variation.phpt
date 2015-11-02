--TEST--
ReflectionClass::getExtension() method - variation test for getExtension()
--CREDITS--
Rein Velt <rein@velt.org>
#testFest Roosendaal 2008-05-10
--FILE--
<?php require 'vendor/autoload.php';

	class myClass
	{	
		public $varX;
		public $varY;
	}
	$rc=\BetterReflection\Reflection\ReflectionClass::createFromName('myClass');
	var_dump( $rc->getExtension()) ;
?>
--EXPECT--
NULL
