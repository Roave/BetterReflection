<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->skip([
        __DIR__ . '/test/unit/Fixture',
    ])
    ->withPreset(Preset::PSR4())
    ->layerPattern('Facade', '/^Roave\\\\BetterReflection\\\\BetterReflection$/')
    ->layerPattern('Identifier', '/^Roave\\\\BetterReflection\\\\Identifier\\\\.*$/')
    ->layerPattern('NodeCompiler', '/^Roave\\\\BetterReflection\\\\NodeCompiler\\\\.*$/')
    ->layerPattern(
        'Reflection',
        '/^Roave\\\\BetterReflection\\\\Reflection\\\\.*$/',
        '/^Roave\\\\BetterReflection\\\\Reflection\\\\Adapter\\\\.*$/'
    )
    ->layerPattern('ReflectionAdapter', '/^Roave\\\\BetterReflection\\\\Reflection\\\\Adapter\\\\.*$/')
    ->layerPattern('Reflector', '/^Roave\\\\BetterReflection\\\\Reflector\\\\.*$/')
    ->layerPattern(
        'SourceLocator',
        [
            '/^Roave\\\\BetterReflection\\\\SourceLocator\\\\FileChecker$/',
            '/^Roave\\\\BetterReflection\\\\SourceLocator\\\\Exception\\\\.*$/',
        ]
    )
    ->layerPattern('SourceLocatorAst', '/^Roave\\\\BetterReflection\\\\SourceLocator\\\\Ast\\\\.*$/')
    ->layerPattern('Located', '/^Roave\\\\BetterReflection\\\\SourceLocator\\\\Located\\\\.*$/')
    ->layerPattern('SourceStubber', '/^Roave\\\\BetterReflection\\\\SourceLocator\\\\SourceStubber\\\\.*$/')
    ->layerPattern(
        'SourceLocatorType',
        '/^Roave\\\\BetterReflection\\\\SourceLocator\\\\Type\\\\.*$/',
        '/^Roave\\\\BetterReflection\\\\SourceLocator\\\\Type\\\\Composer\\\\.*$/'
    )
    ->layerPattern('Composer', '/^Roave\\\\BetterReflection\\\\SourceLocator\\\\Type\\\\Composer\\\\.*$/')
    ->layerPattern('Util', '/^Roave\\\\BetterReflection\\\\Util\\\\.*$/')
    ->ruleset([
        'Facade'            => ['+SourceLocatorType'],
        'Identifier'        => ['Reflection'],
        'NodeCompiler'      => ['Reflection', 'Reflector', 'Util'],
        'Reflection'        => ['+NodeCompiler', 'ReflectionAdapter', 'Facade', 'Located', 'SourceLocatorType'],
        'ReflectionAdapter' => ['Reflection', 'Reflector', 'Util'],
        'Reflector'         => ['Identifier', 'Reflection', 'SourceLocatorType'],
        'SourceLocator'     => [],
        'SourceLocatorAst'  => ['+Reflector', 'Located', 'Util'],
        'Located'           => ['SourceLocator', 'Util'],
        'SourceStubber'     => ['Reflection', 'SourceLocator', 'Util'],
        'SourceLocatorType' => ['+SourceLocatorAst', 'Facade', 'SourceLocator', 'SourceStubber', 'Util'],
        'Composer'          => ['+SourceLocatorType'],
        'Util'              => ['+SourceLocatorAst', 'SourceLocator', 'SourceLocatorType'],
    ]);
