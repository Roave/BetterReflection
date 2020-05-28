<?php

namespace Roave\BetterReflectionTest\Fixture;

function myFunction() {
}

function myFunctionWithParams($a, $b) {
    return $a + $b;
}

function myFunctionWithInternalFunctionCalls() {
    myFunction();
}

function myFunctionWithInternalFunctionCallsExpressions($a, $b) {
    $c = $a + $b;
    myFunction();

    return $c;
}

function myFunctionWithInternalFunctionCallsIf($a, $b) {
    $c = $a + $b;

    if ($c === 3) {
        myFunction();
    }

    return $c;
}

function myFunctionWithInternalFunctionCallsFor() {
    for ($i = 0; $i < 1; $i++) {
        myFunction();
    }
}

function myFunctionWithInternalFunctionCallsForEach($a) {
    foreach ($a as $b) {
        myFunction();
    }
}

function myFunctionWithInternalFunctionCallInFunctionCall() {
    myFunctionWithInternalFunctionCalls();
}

function myFunctionWithMethodCall() {
    $class = new Functions();
    $class->myMethod();
}

function myFunctionWithNewMethodCall() {
    (new Functions())->myMethod();
}

class Functions {
    public function myMethod()
    {
    }

    public function myMethodCallingFunction()
    {
        myFunction();
    }

    public function myMethodCallingSelfMethod()
    {
        $class = new self();
        $class->myMethod();
    }

    public function myMethodCallingThisMethod()
    {
        $this->myMethod();
    }
}
