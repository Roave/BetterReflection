--TEST--
ReflectionMethod::getDocComment() uses left over doc comment from previous scanner run
--INI--
opcache.save_comments=1
--SKIPIF--
<?php
if (!extension_loaded('reflection') || !extension_loaded('tokenizer')) print 'skip missing reflection of tokernizer extension';
?>
--FILE--
<?php require 'vendor/autoload.php';

function strip_doc_comment($c)
{
	if (!strlen($c) || $c === false) return $c;
	return trim(substr($c, 3, -2));
}

token_get_all("<?php require 'vendor/autoload.php';\n/**\n * Foo\n */"); // doc_comment compiler global now contains this Foo comment

eval('class A { }'); // Could also be an include of a file containing similar

$ra = \BetterReflection\Reflection\ReflectionClass::createFromName('A');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(strip_doc_comment($ra->getDocComment()));

token_get_all("<?php require 'vendor/autoload.php';\n/**\n * Foo\n */"); // doc_comment compiler global now contains this Foo comment

include('bug64936.inc');

$rb = \BetterReflection\Reflection\ReflectionClass::createFromName('B');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(strip_doc_comment($rb->getDocComment()));

?>
===DONE===
--EXPECT--
bool(false)
bool(false)
===DONE===
