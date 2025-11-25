<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude('vendor');

return (new Config())
    ->setRules([
        '@PSR12' => true,
        'no_unused_imports' => true,
        'native_function_invocation' => [
            'scope' => 'all',
            'include' => ['@internal', '@compiler_optimized']
        ],
        'native_constant_invocation' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'declare_strict_types' => true
    ])
    ->setFinder($finder);
