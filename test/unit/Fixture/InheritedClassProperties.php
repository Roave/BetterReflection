<?php

trait Bar
{
    public $a;

    private $b;
}

class Baz
{
    public $c;

    protected $d;

    private $e;
}

abstract class Qux extends Baz
{
    use Bar;

    public $f;

    public function unrelatedMethodBeforeConstructor()
    {
    }

    public function __construct($unrelatedParameterBeforePromotedParameter, private $g)
    {
    }
}

