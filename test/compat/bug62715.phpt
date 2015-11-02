--TEST--
Bug #62715 (ReflectionParameter::isDefaultValueAvailable() wrong result)
--FILE--
<?php require 'vendor/autoload.php';

function test(PDO $a = null, $b = 0, array $c) {}
$r = \BetterReflection\Reflection\ReflectionFunction::createFromName('test');

foreach ($r->getParameters() as $p) {
    var_dump($p->isDefaultValueAvailable());
}

foreach ($r->getParameters() as $p) {
    if ($p->isDefaultValueAvailable()) {
        var_dump($p->getDefaultValue());
    }
}
?>
--EXPECT--
bool(true)
bool(true)
bool(false)
NULL
int(0)
