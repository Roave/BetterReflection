# Find a reflection on specified line

Purely an ease-of-use API, the `FindReflectionOnLine` helper simplifies usage
when you want to find a reflection on a specific line. Usage is very simple, as
the class is an invokable class:

```php
<?php

$finder = new FindReflectionOnLine();
$reflection = $finder('path/to/my/file.php', 10);
```

The helper will return `null` if no reflection is found, or may return one of:

* `\BetterReflection\Reflection\ReflectionClass` (for interfaces, classes, traits)
* `\BetterReflection\Reflection\ReflectionMethod`
* `\BetterReflection\Reflection\ReflectionFunction`
