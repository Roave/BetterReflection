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
such a way that it is not already in memory, it is possible to load this class
using Better Reflection's own autoload system (make sure this is added *after*
any other autoloader, otherwise it may not behave correctly.

```php
// Call this anywhere after all other autoloaders are registered (e.g. Composer)
use BetterReflection\Util\Autoload\ClassLoader;
use BetterReflection\Util\Autoload\ClassLoaderMethod\EvalLoader;

$loader = new ClassLoader(new EvalLoader());
$loader->register();

// Call this any time before instantiating the class
$loader->addClass($classInfo);

$c = new MyClass();
var_dump($c->foo()); // This will now be 4, not 5...
```

But, you probably shouldn't do this.
