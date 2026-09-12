<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->skip([
        __DIR__ . '/test/unit/Fixture',
    ])
    ->withPreset(Preset::PSR4())
    ->layer('Root', 'src/BetterReflection.php')
    ->layer('Identifier', 'src/Identifier/')
    ->layer('NodeCompiler', 'src/NodeCompiler/')
    ->layerPattern('Reflection',
        '/^Roave\\\\BetterReflection\\\\Reflection\\\\.*$/',
        '/^Roave\\\\BetterReflection\\\\Reflection\\\\Adapter\\\\.*$/'
    )
    ->layer('ReflectionAdapter', 'src/Reflection/Adapter/')
    ->layer('Reflector', 'src/Reflector/')
    ->layer('SourceLocator', [
        'src/SourceLocator/FileChecker.php',
        'src/SourceLocator/Exception/',
    ])
    ->layer('SourceLocatorAst', 'src/SourceLocator/Ast/')
    ->layer('Located', 'src/SourceLocator/Located/')
    ->layer('SourceStubber', 'src/SourceLocator/SourceStubber/')
    ->layerPattern(
        'SourceLocatorType',
        '/^Roave\\\\BetterReflection\\\\SourceLocator\\\\Type\\\\.*$/',
        '/^Roave\\\\BetterReflection\\\\SourceLocator\\\\Type\\\\Composer\\\\.*$/'
    )
    ->layer('Composer', 'src/SourceLocator/Type/Composer/')
    ->layer('Util', 'src/Util/')
    ->ruleset([
        'Root'              => ['+SourceLocatorType'],
        'Identifier'        => ['Reflection'],
        'NodeCompiler'      => ['Reflection', 'Reflector', 'Util'],
        'Reflection'        => ['+NodeCompiler', 'ReflectionAdapter', 'Root', 'Located', 'SourceLocatorType'],
        'ReflectionAdapter' => ['Reflection', 'Reflector', 'Util'],
        'Reflector'         => ['Identifier', 'Reflection', 'SourceLocatorType'],
        'SourceLocator'     => [],
        'SourceLocatorAst'  => ['+Reflector', 'Located', 'Util'],
        'Located'           => ['SourceLocator', 'Util'],
        'SourceStubber'     => ['Reflection', 'SourceLocator', 'Util'],
        'SourceLocatorType' => ['+SourceLocatorAst', 'Root', 'SourceLocator', 'SourceStubber'],
        'Composer'          => ['+SourceLocatorType'],
        'Util'              => ['+SourceLocatorAst', 'SourceLocator'],
    ]);
