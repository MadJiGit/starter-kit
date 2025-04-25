<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->exclude('var')
    ->exclude('vendor');


return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'binary_operator_spaces' => ['default' => 'single_space'],
        'single_quote' => true,
        'ordered_imports' => true,
        'blank_line_before_statement' => ['statements' => ['return']],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true);
