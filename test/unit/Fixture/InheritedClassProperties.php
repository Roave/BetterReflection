<?php

trait Bar
{
    public $a;

    private $b;

    public $h;

    public $i;
}

class Baz
{
    public $c;

    protected $d;

    private $e;

    public $i;
}

abstract class Qux extends Baz
{
    use Bar;

    public $f;

    public $h;

    public function unrelatedMethodBeforeConstructor()
    {
    }

    public function __construct($unrelatedParameterBeforePromotedParameter, private $g)
    {
    }
}

