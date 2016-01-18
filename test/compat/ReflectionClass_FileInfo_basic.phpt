--TEST--
ReflectionClass::getFileName(), ReflectionClass::getStartLine(), ReflectionClass::getEndLine()
--FILE-- 
<?php require 'vendor/autoload.php';
//New instance of class C - defined below
$rc = \BetterReflection\Reflection\ReflectionClass::createFromName("C");

//Get the file name of the PHP script in which C is defined
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getFileName());

//Get the line number at the start of the definition of class C
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getStartLine());

//Get the line number at the end of the definition of class C
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getEndLine());

//Same tests as above but stdclass is internal - so all results should be false. 
$rc = \BetterReflection\Reflection\ReflectionClass::createFromName("stdClass");
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getFileName());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getStartLine());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getEndLine());

Class C {

}
?>
--EXPECTF--
string(%d) "%sReflectionClass_FileInfo_basic.php"
int(20)
int(22)
bool(false)
bool(false)
bool(false)
