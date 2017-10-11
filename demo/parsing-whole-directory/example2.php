<?php # inspired by https://github.com/Roave/BetterReflection/issues/276

# parse all classes in a directory that use some dependencies from /vendor

require_once __DIR__ . '/vendor/autoload.php';

use Rector\BetterReflection\BetterReflection;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;

$directories = [__DIR__ . '/src'];

$sourceLocator = new AggregateSourceLocator([
    new DirectoriesSourceLocator(
        $directories,
        (new BetterReflection())->astLocator()
    ),
    // ↓ required to autoload parent classes/interface from another directory than /src (e.g. /vendor)
    new AutoloadSourceLocator((new BetterReflection())->astLocator())
]);

$classReflector = new ClassReflector($sourceLocator);

$classReflections = $classReflector->getAllClasses();
