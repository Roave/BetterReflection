<?php # inspired by https://github.com/Roave/BetterReflection/issues/276

# parse all classes in a directory

require_once __DIR__ . '/vendor/autoload.php';

use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;

$directories = [__DIR__ . '/src'];

$sourceLocator = new DirectoriesSourceLocator($directories);

$classReflector = new ClassReflector($sourceLocator);

$classReflections = $classReflector->getAllClasses();
