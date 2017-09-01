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
<?php

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\SetFunctionBodyFromClosure;
use Roave\BetterReflection\Reflection\Mutator\ReflectionMutators;

$betterReflection   = new BetterReflection();
$reflectionMutators = new ReflectionMutators();

$classInfo = $betterReflection->classReflector()->reflect('MyClass');

$methodInfo = $classInfo->getMethod('foo');
$modifiedMethodInfo = (new SetFunctionBodyFromClosure($betterReflection->phpParser(), $reflectionMutators->functionMutator()))($methodInfo, function () {
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
<?php

// Call this anywhere after all other autoloaders are registered (e.g. Composer)
use Roave\BetterReflection\Util\Autoload\ClassLoader;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\FileCacheLoader;

$loader = new ClassLoader(FileCacheLoader::defaultFileCacheLoader(__DIR__));

// Call this any time before instantiating the class
$loader->addClass($classInfo);

$c = new MyClass();
var_dump($c->foo()); // This will now be 4, not 5...
```

But, you probably shouldn't do this ;)

Loader methods available are:

 * `FileCacheLoader` - cache the file contents (no cache invalidation). Example
   usage is above; it's recommended to use the `defaultFileCacheLoader` static
   constructor to simplify creation. Pass the directory to store cached files
   as the parameter.
 * `EvalCacheLoader` - as the naming suggests, uses `eval` to bring the class
   into scope. This is not ideal if you're after performance.
