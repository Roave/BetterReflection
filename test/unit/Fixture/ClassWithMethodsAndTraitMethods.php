<?php

trait TraitWithMethod
{
    private function methodFromTrait()
    {
    }
}

class ClassWithMethodsAndTraitMethods
{
    use TraitWithMethod;

    private function methodFromClass()
    {
    }
}

class ExtendedClassWithMethodsAndTraitMethods extends ClassWithMethodsAndTraitMethods
{
    private function methodFromClass()
    {
    }
}
