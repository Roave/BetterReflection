# Reflection Modification

## Replacing a function or method body

It is possible in Better Reflection to replace the body statements of a function
in the reflection - in essence, giving the ability to monkey patch the code.

Given the following class under reflection:

```php
class MyClass
{
    public function foo()
    {
        return 5;
    }
}
```

You can replace the body of the function like so:

```php
$classInfo = ReflectionClass::createFromName('MyClass');
$classInfo->getMethod('foo')->setBody(function () {
    return 4;
});
```

This does not take immediate effect on execution - and in fact, if the class is
already loaded, it is impossible to overwrite the in-memory class (this is a
restriction in PHP itself). However, if you have reflected on this class in
such a way that it is not in memory, it is possible to export the class and
load it from a temporary location, for example:

```php
use PhpParser\PrettyPrinter\Standard;

$classCode = (new Standard())->prettyPrint($classInfo->getAst());

file_put_contents('/tmp/foo.php', '<?php ' . $classCode);
require_once('/tmp/foo.php');

$c = new MyClass();
var_dump($c->foo()); // This will now be 4, not 5...
```

But, you probably shouldn't do this.
