--TEST--
ReflectionClass and Traits
--FILE--
<?php require 'vendor/autoload.php';
trait Foo {
    public function someMethod() { }
}

class Bar {
    use Foo;

    public function someOtherMethod() { }
}

$rFoo = \BetterReflection\Reflection\ReflectionClass::createFromName('Foo');
$rBar = \BetterReflection\Reflection\ReflectionClass::createFromName('Bar');

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rFoo->isTrait());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rBar->isTrait());
echo $rFoo;
echo $rBar;
--EXPECTF--
bool(true)
bool(false)
Trait [ <user> trait Foo ] {
  @@ %straits001.php 2-4

  - Constants [0] {
  }

  - Static properties [0] {
  }

  - Static methods [0] {
  }

  - Properties [0] {
  }

  - Methods [1] {
    Method [ <user> public method someMethod ] {
      @@ %straits001.php 3 - 3
    }
  }
}
Class [ <user> class Bar ] {
  @@ %straits001.php 6-10

  - Constants [0] {
  }

  - Static properties [0] {
  }

  - Static methods [0] {
  }

  - Properties [0] {
  }

  - Methods [2] {
    Method [ <user> public method someOtherMethod ] {
      @@ %straits001.php 9 - 9
    }

    Method [ <user> public method someMethod ] {
      @@ %straits001.php 3 - 3
    }
  }
}
