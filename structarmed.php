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
    ->layer('Reflection', 'src/Reflection/')
    ->layer('Reflector', 'src/Reflector/')
    ->layer('SourceLocator', [
        'src/SourceLocator/FileChecker.php',
        'src/SourceLocator/Exception/',
    ])
    ->layer('SourceLocatorAst', 'src/SourceLocator/Ast/')
    ->layer('Located', 'src/SourceLocator/Located/')
    ->layer('SourceStubber', 'src/SourceLocator/SourceStubber/')
    ->layer('SourceLocatorType', 'src/SourceLocator/Type/')
    ->layer('Util', 'src/Util/')
    ->ruleset([
        'Root'              => ['+SourceLocatorType'],
        'Identifier'        => ['Reflection'],
        'NodeCompiler'      => ['Reflection', 'Reflector', 'Util'],
        'Reflection'        => ['+NodeCompiler', 'Located', 'Root', 'SourceLocatorType'],
        'Reflector'         => ['+Identifier', 'SourceLocatorType'],
        'SourceLocator'     => [],
        'SourceLocatorAst'  => ['+Reflector', 'Located', 'Util'],
        'Located'           => ['SourceLocator', 'Util'],
        'SourceStubber'     => ['Reflection', 'SourceLocator', 'Util'],
        'SourceLocatorType' => ['+SourceLocatorAst', '+SourceStubber', 'Root'],
        'Util'              => ['+SourceLocatorAst', 'SourceLocator'],
    ]);
