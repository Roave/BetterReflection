<?php

namespace Roave\BetterReflectionTest\Fixture;

use Attribute;

const SOME_CONSTANT = 'some-constant';

#[Attribute]
class Attr
{
}

#[Attribute]
class AnotherAttr extends Attr
{
}

#[Attr]
#[AnotherAttr]
class ClassWithAttributes
{

    #[Attr]
    #[AnotherAttr]
    public const CONSTANT_WITH_ATTRIBUTES = [];

    #[Attr]
    #[AnotherAttr]
    private $propertyWithAttributes = [];

    #[Attr]
    #[AnotherAttr]
    public function methodWithAttributes(
        #[Attr]
        #[AnotherAttr]
        $parameterWithAttributes
    )
    {

    }
}

#[Attr]
#[AnotherAttr]
function functionWithAttributes()
{
}

#[Attr]
#[AnotherAttr]
#[AnotherAttr]
class ClassWithRepeatedAttributes
{

}

#[Attr('arg1', 'arg2', arg3: self::class, arg4: [0, ClassWithAttributes::class, [__CLASS__, ClassWithRepeatedAttributes::class]])]
class ClassWithAttributesWithArguments
{
}

#[Attr]
#[AnotherAttr]
enum EnumWithAttributes
{
    #[Attr]
    #[AnotherAttr]
    case CASE_WITH_ATTRIBUTES;
}
