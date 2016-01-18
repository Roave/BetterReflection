--TEST--
ReflectionObject::getFileName(), ReflectionObject::getStartLine(), ReflectionObject::getEndLine() - basic function
--FILE-- 
<?php require 'vendor/autoload.php';
$rc = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new C);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getFileName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getStartLine());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getEndLine());

$rc = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new stdclass);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getFileName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getStartLine());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getEndLine());

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
