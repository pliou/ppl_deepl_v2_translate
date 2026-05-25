<?php

declare(strict_types=1);

use Ppl\PplDeeplV2Translate\Controller\BackendTranslationController;
use Ppl\PplDeeplV2Translate\Controller\BackendConfigurationController;

$modules = [
    'ppl_deepl_v2' => [
        'position' => ['after' => 'system'],
        'iconIdentifier' => 'module-ppl-deepl-v2',
        'aliases' => ['ppl_deepl'],
        'labels' => [
            'title' => 'LLL:EXT:ppl_deepl_v2_translate/Resources/Private/Language/locallang.xlf:module.root.title',
            'shortDescription' => 'LLL:EXT:ppl_deepl_v2_translate/Resources/Private/Language/locallang.xlf:module.root.description',
        ],
    ],
    'ppl_deepl_v2_configuration' => [
        'parent' => 'ppl_deepl_v2',
        'position' => ['before' => '*'],
        'access' => 'user',
        'path' => '/module/ppl-deepl/v2-configuration',
        'iconIdentifier' => 'module-ppl-deepl-v2-configuration',
        'labels' => [
            'title' => 'LLL:EXT:ppl_deepl_v2_translate/Resources/Private/Language/locallang.xlf:module.configuration.title',
            'shortDescription' => 'LLL:EXT:ppl_deepl_v2_translate/Resources/Private/Language/locallang.xlf:module.configuration.description.v2',
        ],
        'routes' => [
            '_default' => [
                'target' => BackendConfigurationController::class . '::handleRequest',
            ],
        ],
    ],
    'ppl_deepl_v2_translation' => [
        'parent' => 'ppl_deepl_v2',
        'position' => ['after' => 'ppl_deepl_v2_configuration'],
        'access' => 'user',
        'path' => '/module/ppl-deepl/v2-translation',
        'iconIdentifier' => 'module-ppl-deepl-v2-translation',
        'labels' => [
            'title' => 'LLL:EXT:ppl_deepl_v2_translate/Resources/Private/Language/locallang.xlf:module.v2.translation.title',
            'shortDescription' => 'LLL:EXT:ppl_deepl_v2_translate/Resources/Private/Language/locallang.xlf:module.v2.translation.description',
        ],
        'routes' => [
            '_default' => [
                'target' => BackendTranslationController::class . '::handleRequest',
            ],
        ],
    ],
    'ppl_deepl_v2_file_translation' => [
        'parent' => 'ppl_deepl_v2',
        'position' => ['after' => 'ppl_deepl_v2_translation'],
        'access' => 'user',
        'path' => '/module/ppl-deepl/v2-file-translation',
        'iconIdentifier' => 'module-ppl-deepl-v2-file-translation',
        'labels' => [
            'title' => 'LLL:EXT:ppl_deepl_v2_translate/Resources/Private/Language/locallang.xlf:module.v2.file.title',
            'shortDescription' => 'LLL:EXT:ppl_deepl_v2_translate/Resources/Private/Language/locallang.xlf:module.v2.file.description',
        ],
        'routes' => [
            '_default' => [
                'target' => BackendTranslationController::class . '::handleRequest',
            ],
        ],
    ],
];

return $modules;
