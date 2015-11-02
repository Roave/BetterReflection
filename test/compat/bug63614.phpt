--TEST--
Bug #63614 (Fatal error on Reflection)
--FILE--
<?php require 'vendor/autoload.php';
function dummy() {
   static $a = array();
}

class Test
{
    const A = 0;

    public function func()
    {
        static $a  = array(
            self::A   => 'a'
        );
    }
}

$reflect = \BetterReflection\Reflection\ReflectionFunction::createFromName("dummy");
print_r($reflect->getStaticVariables());
$reflect = \BetterReflection\Reflection\ReflectionMethod::createFromName('Test', 'func');
print_r($reflect->getStaticVariables());
?>
--EXPECT--
Array
(
    [a] => Array
        (
        )

)
Array
(
    [a] => Array
        (
            [0] => a
        )

)
