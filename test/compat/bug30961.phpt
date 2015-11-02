--TEST--
Reflection Bug #30961 (Wrong linenumber in ReflectionClass getStartLine())
--FILE--
<?php require 'vendor/autoload.php';
    class a
    {
    }

    class b extends a
    {
    }

    $ref1 = \BetterReflection\Reflection\ReflectionClass::createFromName('a');
    $ref2 = \BetterReflection\Reflection\ReflectionClass::createFromName('b');
    echo $ref1->getStartLine() . "\n";
    echo $ref2->getStartLine() . "\n";
?>
--EXPECT--
2
6
