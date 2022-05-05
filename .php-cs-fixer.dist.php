<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'var',
        'input',
        'output',
        'vendor',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'phpdoc_summary' => false,
        'no_unneeded_final_method' => false,
        'no_superfluous_phpdoc_tags' => true,
        'concat_space' => ['spacing' => 'none'],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'],
        'phpdoc_to_comment' => false,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'PedroTroller/line_break_between_method_arguments' => ['max-args' => 20],
        'PedroTroller/line_break_between_statements' => true,
    ])
    ->setFinder($finder)
    ->registerCustomFixers(new PedroTroller\CS\Fixer\Fixers())
    ->setCacheFile(__DIR__.'/var/cache/.php-cs-fixer.cache')
;
