<?php

interface One
{
}

interface Two extends One
{
}

trait Bar
{
    public $a;

    private $b;

    public $h;

    public $i;
}

class Baz implements One
{
    public $c;

    protected $d;

    private $e;

    public $i;
}

abstract class Qux extends Baz implements Two
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

