<?php
/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:2.16.3|configurator
 * you can change this configuration by importing this file.
 */
return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        '@PhpCsFixer' => true,
        '@Symfony' => true,
        '@PHP73Migration' => true,
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
        'fully_qualified_strict_types' => false,
    ])
    ->setFinder(PhpCsFixer\Finder::create()
    ->exclude(['vendor'])
    ->in(__DIR__)
    )
;
