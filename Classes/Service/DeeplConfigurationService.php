<?php

declare(strict_types=1);

namespace Ppl\PplDeeplV2Translate\Service;

final class DeeplConfigurationService
{
    public function getAuthKey(array $settings = []): string
    {
        $extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ppl_deepl_v2_translate'] ?? [];
        if (is_array($extensionConfiguration)) {
            return trim((string)($extensionConfiguration['authKey'] ?? ''));
        }

        return '';
    }

    public function getLoginPageUid(array $settings = []): int
    {
        if (isset($settings['loginPageUid']) && (int)$settings['loginPageUid'] > 0) {
            return (int)$settings['loginPageUid'];
        }

        $extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ppl_deepl_v2_translate'] ?? [];
        if (is_array($extensionConfiguration) && (int)($extensionConfiguration['loginPageUid'] ?? 0) > 0) {
            return (int)$extensionConfiguration['loginPageUid'];
        }

        return 95;
    }
}
