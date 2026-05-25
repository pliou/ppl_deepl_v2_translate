<?php

defined('TYPO3') or die();

call_user_func(function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:ppl_deepl_v2_translate/Resources/Private/Language/locallang.xlf:plugin.v2.text.title',
            'ppldeeplv2translate_deepl',
            null,
        ],
        'list_type',
        'ppl_deepl_v2_translate'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:ppl_deepl_v2_translate/Resources/Private/Language/locallang.xlf:plugin.v2.file.title',
            'ppldeeplv2translate_deeplfile',
            null,
        ],
        'list_type',
        'ppl_deepl_v2_translate'
    );
});
