<?php

class Php4StyleConstruct
{
    public function Php4StyleConstruct()
    {
    }
}

$anonymousClass = new class extends Php4StyleConstruct
{
    public function notConstructor()
    {
    }
};
