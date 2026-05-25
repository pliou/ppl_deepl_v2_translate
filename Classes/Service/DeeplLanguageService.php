<?php

declare(strict_types=1);

namespace Ppl\PplDeeplV2Translate\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

final class DeeplLanguageService
{
    public const DEFAULT_SOURCE_LANGUAGE = 'EN';
    public const DEFAULT_TARGET_LANGUAGE = 'DE';

    public function getLanguages(): array
    {
        return GeneralUtility::makeInstance(LanguageConfigurationService::class)->getEnabledLanguages();
    }

    public function getSourceLanguages(): array
    {
        return GeneralUtility::makeInstance(LanguageConfigurationService::class)->getEnabledSourceLanguages();
    }

    public function getTargetLanguages(): array
    {
        return GeneralUtility::makeInstance(LanguageConfigurationService::class)->getEnabledTargetLanguages();
    }

    public function normalizeSourceLanguage(string $language): string
    {
        $language = strtoupper($language);

        return match (true) {
            $language === 'DE-DE' => 'DE',
            str_starts_with($language, 'EN-') => 'EN',
            str_starts_with($language, 'PT-') => 'PT',
            str_starts_with($language, 'ES-') => 'ES',
            $language === 'ZH-HANS' || $language === 'ZH-HANT' => 'ZH',
            default => $language,
        };
    }

    public function normalizeTargetLanguage(string $language): string
    {
        return match (strtoupper($language)) {
            'EN' => 'EN-GB',
            'PT' => 'PT-PT',
            'DE-DE' => 'DE',
            default => strtoupper($language),
        };
    }

    public function normalizeGlossaryLanguage(string $language): string
    {
        $language = strtoupper($language);

        return match (true) {
            $language === 'DE-DE' => 'DE',
            str_starts_with($language, 'EN-') => 'EN',
            str_starts_with($language, 'PT-') => 'PT',
            str_starts_with($language, 'ES-') => 'ES',
            $language === 'ZH-HANS' || $language === 'ZH-HANT' => 'ZH',
            default => $language,
        };
    }

    public function normalizeWriteLanguage(string $language): string
    {
        return match (true) {
            str_starts_with(strtoupper($language), 'EN') => 'EN',
            strtoupper($language) === 'DE' || strtoupper($language) === 'DE-DE' => 'DE',
            default => $this->normalizeSourceLanguage($language),
        };
    }

    public function supportsDeepLWriteLanguage(string $language): bool
    {
        $language = strtoupper($language);

        return str_starts_with($language, 'EN') || $language === 'DE';
    }

    public function buildGlossaryCombinationKey(string $sourceLanguage, string $targetLanguage): string
    {
        return $this->normalizeGlossaryLanguage($sourceLanguage)
            . ':'
            . $this->normalizeGlossaryLanguage($targetLanguage);
    }
}
