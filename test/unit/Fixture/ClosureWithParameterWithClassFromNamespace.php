<?php

namespace Roave\BetterReflectionTest\Fixture;

class ClassUsedAsClosureParameter
{
}

return function (ClassUsedAsClosureParameter $parameter)
{
    echo 'Hello world!';
};
