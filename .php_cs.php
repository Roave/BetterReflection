<?php

$finder = Symfony\CS\Finder\DefaultFinder::create();
$config = Symfony\CS\Config\Config::create();

$config->level(null);
$config->fixers([
    'psr0',
    'encoding',
    'short_tag',
    'braces',
    'elseif',
    'eof_ending',
    'function_call_space',
    'function_declaration',
    'indentation',
    'line_after_namespace',
    'linefeed',
    'lowercase_constants',
    'lowercase_keywords',
    'method_argument_space',
    'multiple_use',
    'parenthesis',
    'php_closing_tag',
    'single_line_after_imports',
    'trailing_spaces',
    'visibility',
    'blankline_after_open_tag',
    'extra_empty_lines',
    'join_function',
    'multiline_array_trailing_comma',
    'namespace_no_leading_whitespace',
    'new_with_braces',
    'no_blank_lines_after_class_opening',
    'no_empty_lines_after_phpdocs',
    'object_operator',
    'phpdoc_indent',
    'phpdoc_no_access',
    'phpdoc_no_package',
    'phpdoc_scalar',
    'phpdoc_trim',
    'phpdoc_type_to_var',
    'phpdoc_var_without_name',
    'remove_leading_slash_use',
    'remove_lines_between_uses',
    'self_accessor',
    'single_array_no_trailing_comma',
    'single_blank_line_before_namespace',
    'single_quote',
    'standardize_not_equal',
    'ternary_spaces',
    'trim_array_spaces',
    'whitespacy_lines',
    'concat_with_spaces',
    'newline_after_open_tag',
    'short_array_syntax',
    'unused_use',
]);

$finder->in(__DIR__)->exclude('test/unit/Fixture');
$config->finder($finder);

return $config;
