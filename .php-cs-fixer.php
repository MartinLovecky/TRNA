<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude('vendor');

return (new Config())
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@PhpCsFixer:risky' => true,

        // Imports
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],

        // Arrays
        'array_syntax' => ['syntax' => 'short'],
        'trim_array_spaces' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments']],

        // Multiline function call formatting
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],

        // Whitespace
        'no_trailing_whitespace' => true,
        'no_extra_blank_lines' => true,
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,

        // Strings
        'single_quote' => false, // leave double quotes

        // PHPDoc
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_order' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_superfluous_phpdoc_tags' => true,

        // Strictness
        'strict_comparison' => true,
        'strict_param' => true,
    ])
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setParallelConfig(ParallelConfigFactory::detect());
