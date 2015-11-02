--TEST--
ReflectionObject::getFileName(), ReflectionObject::getStartLine(), ReflectionObject::getEndLine() - basic function
--FILE-- 
<?php require 'vendor/autoload.php';
$rc = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new C);
var_dump($rc->getFileName());
var_dump($rc->getStartLine());
var_dump($rc->getEndLine());

$rc = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new stdclass);
var_dump($rc->getFileName());
var_dump($rc->getStartLine());
var_dump($rc->getEndLine());

Class C {

}
?>
--EXPECTF--
string(%d) "%sReflectionObject_FileInfo_basic.php"
int(12)
int(14)
bool(false)
bool(false)
bool(false)
