--TEST--
Reflection Bug #36308 (ReflectionProperty::getDocComment() does not reflect extended class commentary)
--INI--
opcache.save_comments=1
--FILE--
<?php require 'vendor/autoload.php';
class Base {
    /** Base comment block */
    public $foo = 'bar';
}

class Extended extends Base {
    /** Extended commentary */
    public $foo = 'zim';
}

$reflect = \BetterReflection\Reflection\ReflectionClass::createFromName('Extended');
$props = $reflect->getProperties();
echo $props[0]->getDocComment();
?>
--EXPECT--
/** Extended commentary */
