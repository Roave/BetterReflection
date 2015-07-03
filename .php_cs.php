<?php

$finder = Symfony\CS\Finder\DefaultFinder::create();
$config = Symfony\CS\Config\Config::create();

$config->level(\Symfony\CS\FixerInterface::PSR2_LEVEL);
$finder->in(__DIR__)->exclude('test/unit/Fixture');

$config->finder($finder);

return $config;
