<?php

namespace Ppl\PplDeeplV2Translate\Controller;

use Ppl\PplDeeplV2Translate\Service\DeeplConfigurationService;
use Ppl\PplDeeplV2Translate\Service\DeeplGlossaryService;
use Ppl\PplDeeplV2Translate\Service\DeeplLanguageService;
use Ppl\PplDeeplV2Translate\Service\DeeplTranslationService;
use Ppl\PplDeeplV2Translate\Service\Api\V2DeepLPhpAdapter;
use Ppl\PplDeeplV2Translate\Service\FrontendAccessService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DeeplController extends ActionController
{
    public function interfaceAction(): ResponseInterface
    {
        $frontendAccessService = GeneralUtility::makeInstance(FrontendAccessService::class);
        $accessResponse = $frontendAccessService->buildAccessResponse((array)$this->settings, $this->request, $this->uriBuilder);
        if ($accessResponse !== null) {
            return $accessResponse;
        }

        $languageService = GeneralUtility::makeInstance(DeeplLanguageService::class);
        $apiAdapter = GeneralUtility::makeInstance(V2DeepLPhpAdapter::class, $languageService);
        $glossaryService = GeneralUtility::makeInstance(DeeplGlossaryService::class, $languageService, $apiAdapter);
        $translationService = GeneralUtility::makeInstance(DeeplTranslationService::class, $languageService, $glossaryService, $apiAdapter);
        $configurationService = GeneralUtility::makeInstance(DeeplConfigurationService::class);

        $authKey = $configurationService->getAuthKey();

        $inputText = '';
        $translatedText = null;
        $translationError = null;

        $useGlossary = true;
        $selectedSourceLanguage = DeeplLanguageService::DEFAULT_SOURCE_LANGUAGE;
        $selectedTargetLanguage = DeeplLanguageService::DEFAULT_TARGET_LANGUAGE;
        $selectedGlossaryId = '';
        $selectedWritingStyle = '';
        $selectedTone = '';

        $writingStyles = $translationService->getWritingStyles();
        $tones = $translationService->getTones();
        $languages = $languageService->getLanguages();
        $sourceLanguages = $languageService->getSourceLanguages();
        $targetLanguages = $languageService->getTargetLanguages();

        if ($this->request->hasArgument('language_source')) {
            $requestedSourceLanguage = trim((string)$this->request->getArgument('language_source'));
            if (array_key_exists($requestedSourceLanguage, $sourceLanguages)) {
                $selectedSourceLanguage = $requestedSourceLanguage;
            } elseif (array_key_exists($languageService->normalizeSourceLanguage($requestedSourceLanguage), $sourceLanguages)) {
                $selectedSourceLanguage = $languageService->normalizeSourceLanguage($requestedSourceLanguage);
            }
        }

        if ($this->request->hasArgument('language_ziel')) {
            $requestedTargetLanguage = trim((string)$this->request->getArgument('language_ziel'));
            if (array_key_exists($requestedTargetLanguage, $targetLanguages)) {
                $selectedTargetLanguage = $requestedTargetLanguage;
            }
        }

        $glossaryOptions = $glossaryService->getGlossaryOptionsForLanguagePair($selectedSourceLanguage, $selectedTargetLanguage);
        $useGlossary = false;

        if ($this->request->hasArgument('textarea')) {
            $inputText = trim((string)$this->request->getArgument('textarea'));

            $selectedGlossaryId = $this->request->hasArgument('glossary_id')
                ? trim((string)$this->request->getArgument('glossary_id'))
                : '';
            if (!$glossaryService->isGlossaryAvailableForLanguagePair($selectedGlossaryId, $selectedSourceLanguage, $selectedTargetLanguage)) {
                $selectedGlossaryId = '';
            }
            $useGlossary = $selectedGlossaryId !== '';

            $selectedWritingStyle = $this->getSubmittedStringArgument('writing_style');
            $selectedTone = $this->getSubmittedStringArgument('tone');

            if (!array_key_exists($selectedWritingStyle, $writingStyles)) {
                $selectedWritingStyle = '';
            }

            if (!array_key_exists($selectedTone, $tones)) {
                $selectedTone = '';
            }

            /**
             * Only one of writing_style or tone is allowed.
             * If both are submitted, keep writing_style and discard tone.
             */
            if ($selectedWritingStyle !== '' && $selectedTone !== '') {
                $selectedTone = '';
            }

            /**
             * If the selected language pair has no glossary, force glossary off.
             * This keeps backend behavior safe even if frontend JS is bypassed.
             */
            if ($inputText === '') {
                $translationError = $this->translate('error.missingText');
            } elseif ($this->isSameLanguagePair($languageService, $selectedSourceLanguage, $selectedTargetLanguage)) {
                $translationError = $this->translate('error.sameLanguage');
            } elseif ($authKey === '') {
                $translationError = $this->translate('error.missingAuthKey.v2');
            } else {
                try {
                    $translatedText = $translationService->translateText(
                        $authKey,
                        $inputText,
                        $selectedSourceLanguage,
                        $selectedTargetLanguage,
                        $selectedGlossaryId,
                        $selectedWritingStyle,
                        $selectedTone
                    );
                } catch (\Throwable $exception) {
                    $translationError = $this->translate('error.translation', [$exception->getMessage()]);
                }
            }
        }

        $selectionMode = 'none';
        $selectionLabel = $this->translate('option.disabled');

        if ($selectedWritingStyle !== '') {
            $selectionMode = 'writing_style';
            $selectionLabel = $writingStyles[$selectedWritingStyle] ?? $selectedWritingStyle;
        } elseif ($selectedTone !== '') {
            $selectionMode = 'tone';
            $selectionLabel = $tones[$selectedTone] ?? $selectedTone;
        }

        $this->view->assignMultiple([
            'frontendAccessHeader' => $frontendAccessService->renderAccessHeader($this->request),
            'textarea' => $inputText,
            'translatedText' => $translatedText,
            'translationError' => $translationError,
            'useGlossary' => $useGlossary,
            'languages' => $languages,
            'sourceLanguages' => $sourceLanguages,
            'targetLanguages' => $targetLanguages,
            'language_source' => $selectedSourceLanguage,
            'language_ziel' => $selectedTargetLanguage,
            'sourceLanguageLabel' => $sourceLanguages[$selectedSourceLanguage] ?? $languages[$selectedSourceLanguage] ?? 'English',
            'sourceLanguageCode' => $selectedSourceLanguage,
            'targetLanguageLabel' => $targetLanguages[$selectedTargetLanguage] ?? $languages[$selectedTargetLanguage] ?? 'English (UK)',
            'targetLanguageCode' => $selectedTargetLanguage,
            'writingStyles' => $writingStyles,
            'writing_style' => $selectedWritingStyle,
            'tones' => $tones,
            'tone' => $selectedTone,
            'selectionMode' => $selectionMode,
            'selectionLabel' => $selectionLabel,
            'sameLanguageSelected' => $this->isSameLanguagePair($languageService, $selectedSourceLanguage, $selectedTargetLanguage),
            'glossaryAvailable' => $glossaryService->hasGlossaryForLanguagePair($selectedSourceLanguage, $selectedTargetLanguage),
            'glossaryCombinationsJson' => json_encode((object)$glossaryService->getGlossaryCombinations(), JSON_THROW_ON_ERROR),
            'glossaryOptions' => $glossaryOptions,
            'glossaryOptionsByCombinationJson' => json_encode((object)$glossaryService->getGlossaryOptionsByCombination(), JSON_THROW_ON_ERROR),
            'glossary_id' => $selectedGlossaryId,
        ]);

        return $this->htmlResponse();
    }

    private function translate(string $key, array $arguments = []): string
    {
        $label = LocalizationUtility::translate($key, 'PplDeeplV2Translate');
        if (!is_string($label)) {
            return $key;
        }

        return $arguments !== [] ? sprintf($label, ...$arguments) : $label;
    }

    private function getSubmittedStringArgument(string $name): string
    {
        if ($this->request->hasArgument($name)) {
            return $this->normalizeSubmittedValue($this->request->getArgument($name));
        }

        return $this->normalizeSubmittedValue($this->findSubmittedPostValue($_POST, $name));
    }

    private function findSubmittedPostValue(array $values, string $name): mixed
    {
        if (array_key_exists($name, $values)) {
            return $values[$name];
        }

        foreach ($values as $value) {
            if (is_array($value) && array_key_exists($name, $value)) {
                return $value[$name];
            }
        }

        return null;
    }

    private function normalizeSubmittedValue(mixed $value): string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        return is_scalar($value) ? trim((string)$value) : '';
    }

    private function isSameLanguagePair(DeeplLanguageService $languageService, string $sourceLanguage, string $targetLanguage): bool
    {
        return $languageService->normalizeGlossaryLanguage($sourceLanguage) === $languageService->normalizeGlossaryLanguage($targetLanguage);
    }
}
