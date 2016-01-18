--TEST--
ReflectionMethod::getDocComment()
--INI--
opcache.save_comments=1
--FILE--
<?php require 'vendor/autoload.php';
/**
 * My Doc Comment for A
 */
class A {
    /**
     * My Doc Comment for A::f
     */
    function f() {}

    /**
     * My Doc Comment for A::privf
     */
    private function privf() {}

    /** My Doc Comment for A::protStatf */
    protected static function protStatf() {}

    /**

     * My Doc Comment for A::finalStatPubf
     */
	final static public function finalStatPubf() {}
	
}


class B extends A {
    /*** Not a doc comment */
    function f() {}

    /** *
     * My Doc Comment for B::privf
     */




    private function privf() {}


    /** My Doc Comment for B::protStatf 




    */
    protected static function protStatf() {}

}

foreach (array('A', 'B') as $class) {
    $rc = \BetterReflection\Reflection\ReflectionClass::createFromName($class);
    $rms = $rc->getMethods();
    foreach ($rms as $rm) {
        echo "\n\n---> Doc comment for $class::" . $rm->getName() . "():\n";
        // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rm->getDocComment());
    }
}
?>
--EXPECTF--


---> Doc comment for A::f():
string(%d) "/**
     * My Doc Comment for A::f
     */"


---> Doc comment for A::privf():
string(%d) "/**
     * My Doc Comment for A::privf
     */"


---> Doc comment for A::protStatf():
string(%d) "/** My Doc Comment for A::protStatf */"


---> Doc comment for A::finalStatPubf():
string(%d) "/**

     * My Doc Comment for A::finalStatPubf
     */"


---> Doc comment for B::f():
bool(false)


---> Doc comment for B::privf():
string(%d) "/** *
     * My Doc Comment for B::privf
     */"


---> Doc comment for B::protStatf():
string(%d) "/** My Doc Comment for B::protStatf 




    */"


---> Doc comment for B::finalStatPubf():
string(%d) "/**

     * My Doc Comment for A::finalStatPubf
     */"
