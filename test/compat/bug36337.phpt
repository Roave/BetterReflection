--TEST--
Reflection Bug #36337 (ReflectionProperty fails to return correct visibility)
--FILE--
<?php require 'vendor/autoload.php';

abstract class enum {
    protected $_values;

    public function __construct() {
        $property = \BetterReflection\Reflection\ReflectionProperty::createFromName(get_class($this),'_values');
        var_dump($property->isProtected());
    }

}

final class myEnum extends enum {
    public $_values = array(
           0 => 'No value',
       );
}

$x = new myEnum();

echo "Done\n";
?>
--EXPECT--	
bool(false)
Done
