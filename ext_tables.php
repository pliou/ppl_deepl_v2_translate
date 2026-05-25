<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::registerPlugin(
    'PplDeeplV2Translate',
    'Deepl',
    'LLL:EXT:ppl_deepl_v2_translate/Resources/Private/Language/locallang.xlf:plugin.v2.text.title'
);

ExtensionManagementUtility::addStaticFile(
    'ppl_deepl_v2_translate',
    'Configuration/TypoScript',
    'LLL:EXT:ppl_deepl_v2_translate/Resources/Private/Language/locallang.xlf:plugin.v2.static.title'
);

ExtensionUtility::registerPlugin(
    'PplDeeplV2Translate',
    'DeeplFile',
    'LLL:EXT:ppl_deepl_v2_translate/Resources/Private/Language/locallang.xlf:plugin.v2.file.title'
);
