<?php

trait TraitWithProperty
{
    private $propertyFromTrait;
}

class ClassWithPropertiesAndTraitProperties
{
    use TraitWithProperty;

    private $propertyFromClass;
}

class ExtendedClassWithPropertiesAndTraitProperties extends ClassWithPropertiesAndTraitProperties
{
    private $propertyFromClass;
}
