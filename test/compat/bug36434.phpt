--TEST--
Reflection Bug #36434 (Properties from parent class fail to indetify their true origin)
--FILE--
<?php require 'vendor/autoload.php';
class ancester
{
    public $ancester = 0;
	function __construct()
	{
		return $this->ancester;
	}
}
class foo extends ancester
{
    public $bar = "1";
	function __construct()
	{
		return $this->bar;
	}
}

$r = \BetterReflection\Reflection\ReflectionClass::createFromName('foo');
foreach ($r->GetProperties() as $p)
{
	echo $p->getName(). " ". $p->getDeclaringClass()->getName()."\n";
}

?>
--EXPECT--	
bar foo
ancester ancester
