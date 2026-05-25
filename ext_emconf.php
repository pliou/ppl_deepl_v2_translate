<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'PPL DeepL V2 Translate',
    'description' => 'DeepL V2 text and file translation for TYPO3 12.4 with frontend plugins, backend modules, glossary support and language configuration.',
    'category' => 'plugin',
    'author' => 'Pawel Pliousnin',
    'author_email' => 'pliousnin@ppl-ds.com',
    'state' => 'stable',
    'version' => '12.4.0',
    'clearCacheOnLoad' => 0,
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
            'backend' => '12.4.0-12.4.99',
            'extbase' => '12.4.0-12.4.99',
            'fluid' => '12.4.0-12.4.99',
            'fluid_styled_content' => '12.4.0-12.4.99',
            'frontend' => '12.4.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
