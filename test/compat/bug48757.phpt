--TEST--
Bug #48757 (ReflectionFunction::invoke() parameter issues)
--FILE--
<?php require 'vendor/autoload.php';
function test() {
	echo "Hello World\n";
}

function another_test($parameter) {
	var_dump($parameter);
}

$func = \BetterReflection\Reflection\ReflectionFunction::createFromName('test');
$func->invoke();

$func = \BetterReflection\Reflection\ReflectionFunction::createFromName('another_test');
$func->invoke('testing');
?>
--EXPECT--
Hello World
string(7) "testing"
