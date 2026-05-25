<?php

declare(strict_types=1);

namespace Ppl\PplDeeplV2Translate\Service\Api;

use DeepL\DeepLClient;
use DeepL\TranslateTextOptions;
use Ppl\PplDeeplV2Translate\Service\DeeplLanguageService;

final class V2DeepLPhpAdapter implements DeepLApiAdapterInterface
{
    public function __construct(
        private readonly DeeplLanguageService $languageService
    ) {}

    public function getCapabilities(): array
    {
        return [
            'supportsGlossaries' => true,
            'supportsFileTranslation' => true,
            'supportsWritingStyleTone' => true,
            'supportsStyleRules' => false,
            'supportsCustomInstructions' => false,
        ];
    }

    public function fetchLanguages(string $authKey): array
    {
        if (trim($authKey) === '') {
            return [];
        }

        $client = new DeepLClient($authKey);
        $sourceLanguages = $client->getSourceLanguages();
        $targetLanguages = $client->getTargetLanguages();
        $languages = [];

        foreach ($sourceLanguages as $language) {
            $code = $this->normalizeCode((string)($language->code ?? ''));
            if ($code === '') {
                continue;
            }

            $languages[$code] = [
                'code' => $code,
                'name' => (string)($language->name ?? $code),
                'enabled' => false,
                'supportsSource' => true,
                'supportsTarget' => false,
            ];
        }

        foreach ($targetLanguages as $language) {
            $code = $this->normalizeCode((string)($language->code ?? ''));
            if ($code === '') {
                continue;
            }

            if (!isset($languages[$code])) {
                $languages[$code] = [
                    'code' => $code,
                    'name' => (string)($language->name ?? $code),
                    'enabled' => false,
                    'supportsSource' => false,
                    'supportsTarget' => true,
                ];
                continue;
            }

            $languages[$code]['name'] = (string)($language->name ?? $languages[$code]['name']);
            $languages[$code]['supportsTarget'] = true;
        }

        return array_values($languages);
    }

    public function fetchGlossaries(string $authKey): array
    {
        if (trim($authKey) === '') {
            return [];
        }

        $client = new DeepLClient($authKey);
        $glossaries = $client->listMultilingualGlossaries();
        $normalizedGlossaries = [];

        foreach ($glossaries as $glossary) {
            if (!is_object($glossary) && !is_array($glossary)) {
                continue;
            }

            $id = $this->readFirstString($glossary, ['glossaryId', 'id']);
            if ($id === '') {
                continue;
            }

            $dictionariesValue = $this->readRawValue($glossary, 'dictionaries');
            $dictionaries = $this->normalizeDictionaries(is_array($dictionariesValue) ? $dictionariesValue : []);
            $normalizedGlossaries[] = [
                'id' => $id,
                'name' => $this->readFirstString($glossary, ['name']) ?: $id,
                'creationTime' => $this->formatCreationTime($this->readRawValue($glossary, 'creationTime')),
                'dictionaries' => $dictionaries,
                'languagePairs' => $this->buildLanguagePairs($dictionaries),
                'entryCount' => array_sum(array_map(static fn(array $dictionary): int => (int)$dictionary['entryCount'], $dictionaries)),
            ];
        }

        usort(
            $normalizedGlossaries,
            static fn(array $left, array $right): int => strcasecmp((string)$left['name'], (string)$right['name'])
        );

        return $normalizedGlossaries;
    }

    public function translateText(
        string $authKey,
        string $inputText,
        string $sourceLanguage,
        string $targetLanguage,
        ?string $glossaryId = null,
        string $writingStyle = '',
        string $tone = '',
        string $styleRuleId = '',
        array $customInstructions = []
    ): string {
        $client = new DeepLClient($authKey);
        $translateOptions = [];

        if ($glossaryId !== null && $glossaryId !== '') {
            $translateOptions[TranslateTextOptions::GLOSSARY] = $glossaryId;
        }

        $translationResult = $client->translateText(
            $inputText,
            $this->languageService->normalizeSourceLanguage($sourceLanguage),
            $this->languageService->normalizeTargetLanguage($targetLanguage),
            $translateOptions
        );

        $translatedText = $translationResult->text;

        if (
            ($writingStyle !== '' || $tone !== '')
            && $this->languageService->supportsDeepLWriteLanguage($targetLanguage)
        ) {
            $rephraseOptions = [];

            if ($writingStyle !== '') {
                $rephraseOptions['writing_style'] = $writingStyle;
            } elseif ($tone !== '') {
                $rephraseOptions['tone'] = $tone;
            }

            $rephrasedResult = $client->rephraseText(
                $translatedText,
                $this->languageService->normalizeWriteLanguage($targetLanguage),
                $rephraseOptions
            );

            $translatedText = $rephrasedResult->text;
        }

        return $translatedText;
    }

    public function translateDocument(
        string $authKey,
        string $sourcePath,
        string $targetPath,
        string $sourceLanguage,
        string $targetLanguage,
        ?string $glossaryId = null
    ): void {
        $client = new DeepLClient($authKey);
        $options = [];

        if ($glossaryId !== null && $glossaryId !== '') {
            $options['glossary'] = $glossaryId;
        }

        $client->translateDocument(
            $sourcePath,
            $targetPath,
            $this->languageService->normalizeSourceLanguage($sourceLanguage),
            $this->languageService->normalizeTargetLanguage($targetLanguage),
            $options
        );
    }

    private function normalizeDictionaries(array $dictionaries): array
    {
        $normalized = [];

        foreach ($dictionaries as $dictionary) {
            if (!is_object($dictionary) && !is_array($dictionary)) {
                continue;
            }

            $sourceLanguage = $this->readValue($dictionary, 'sourceLang');
            $targetLanguage = $this->readValue($dictionary, 'targetLang');
            if ($sourceLanguage === '' || $targetLanguage === '') {
                continue;
            }

            $sourceLanguage = $this->languageService->normalizeGlossaryLanguage($sourceLanguage);
            $targetLanguage = $this->languageService->normalizeGlossaryLanguage($targetLanguage);
            $normalized[] = [
                'sourceLang' => $sourceLanguage,
                'targetLang' => $targetLanguage,
                'entryCount' => (int)$this->readValue($dictionary, 'entryCount'),
                'combinationKey' => $this->languageService->buildGlossaryCombinationKey($sourceLanguage, $targetLanguage),
            ];
        }

        return $normalized;
    }

    private function readValue(object|array $source, string $key): string
    {
        $value = $this->readRawValue($source, $key);

        return is_scalar($value) ? (string)$value : '';
    }

    private function readFirstString(object|array $source, array $keys): string
    {
        foreach ($keys as $key) {
            $value = $this->readValue($source, (string)$key);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function readRawValue(object|array $source, string $key): mixed
    {
        if (is_array($source)) {
            return $source[$key] ?? null;
        }

        $properties = get_object_vars($source);

        return $properties[$key] ?? null;
    }

    private function buildLanguagePairs(array $dictionaries): string
    {
        $pairs = array_map(
            static fn(array $dictionary): string => $dictionary['sourceLang'] . ' -> ' . $dictionary['targetLang'],
            $dictionaries
        );

        return implode(', ', $pairs);
    }

    private function formatCreationTime(mixed $creationTime): string
    {
        if ($creationTime instanceof \DateTimeInterface) {
            return $creationTime->format(DATE_ATOM);
        }

        return is_scalar($creationTime) ? (string)$creationTime : '';
    }

    private function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }
}
