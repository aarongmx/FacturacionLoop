<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Exception\Configuration\InvalidConfigurationException;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Set\LaravelSetProvider;

try {
    return RectorConfig::configure()
        ->withPaths([
            __DIR__.'/app',
            __DIR__.'/bootstrap/app.php',
            __DIR__.'/database',
            __DIR__.'/public',
        ])
        ->withSkip([
            AddOverrideAttributeToOverriddenMethodsRector::class,
        ])
        ->withSetProviders(LaravelSetProvider::class)
        ->withSets([
            LaravelSetList::ARRAY_STR_FUNCTIONS_TO_STATIC_CALL,
            LaravelSetList::LARAVEL_COLLECTION,
            LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
            LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
            LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
            LaravelSetList::LARAVEL_FACTORIES,
            LaravelSetList::LARAVEL_CODE_QUALITY,
            LaravelSetList::LARAVEL_TESTING,
            LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
        ])
        ->withComposerBased(laravel: true)
        ->withPreparedSets(
            deadCode: true,
            codeQuality: true,
            codingStyle: true,
            typeDeclarations: true,
            privatization: true,
            earlyReturn: true,
        )
        ->withPhpSets();
} catch (InvalidConfigurationException $e) {
    logger($e->getMessage());
}
