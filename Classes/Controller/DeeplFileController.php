<?php

namespace Ppl\PplDeeplV2Translate\Controller;

use Ppl\PplDeeplV2Translate\Service\DeeplConfigurationService;
use Ppl\PplDeeplV2Translate\Service\DeeplGlossaryService;
use Ppl\PplDeeplV2Translate\Service\DeeplLanguageService;
use Ppl\PplDeeplV2Translate\Service\DeeplTranslationService;
use Ppl\PplDeeplV2Translate\Service\Api\V2DeepLPhpAdapter;
use Ppl\PplDeeplV2Translate\Service\FrontendAccessService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class DeeplFileController extends ActionController
{
    public function indexAction(): ResponseInterface
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

        $translatedFilePath = null;
        $translatedFileName = null;
        $errorMessage = null;
        $authKey = $configurationService->getAuthKey();
        $languages = $languageService->getLanguages();
        $sourceLanguages = $languageService->getSourceLanguages();
        $targetLanguages = $languageService->getTargetLanguages();
        $languageSource = DeeplLanguageService::DEFAULT_SOURCE_LANGUAGE;
        $languageDest = DeeplLanguageService::DEFAULT_TARGET_LANGUAGE;
        $selectedGlossaryId = '';

        if ($this->request->hasArgument('language_source')) {
            $requestedLanguage = (string)$this->request->getArgument('language_source');
            if (array_key_exists($requestedLanguage, $sourceLanguages)) {
                $languageSource = $requestedLanguage;
            } elseif (array_key_exists($languageService->normalizeSourceLanguage($requestedLanguage), $sourceLanguages)) {
                $languageSource = $languageService->normalizeSourceLanguage($requestedLanguage);
            }
        }

        if ($this->request->hasArgument('language_ziel')) {
            $requestedLanguage = (string)$this->request->getArgument('language_ziel');
            if (array_key_exists($requestedLanguage, $targetLanguages)) {
                $languageDest = $requestedLanguage;
            }
        }

        if ($this->request->hasArgument('glossary_id')) {
            $selectedGlossaryId = trim((string)$this->request->getArgument('glossary_id'));
            if (!$glossaryService->isGlossaryAvailableForLanguagePair($selectedGlossaryId, $languageSource, $languageDest)) {
                $selectedGlossaryId = '';
            }
        }

        if (
            isset($_FILES['tx_ppldeeplv2translate_deeplfile']['tmp_name']['userfile'])
            && is_uploaded_file($_FILES['tx_ppldeeplv2translate_deeplfile']['tmp_name']['userfile'])
        ) {
            $uploadedFile = $_FILES['tx_ppldeeplv2translate_deeplfile'];
            $tmpFile = $uploadedFile['tmp_name']['userfile'];
            $originalName = (string)$uploadedFile['name']['userfile'];
            $originalExtension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExtensions = ['txt', 'pdf', 'docx', 'pptx'];

            if (!in_array($originalExtension, $allowedExtensions, true)) {
                $errorMessage = $this->translate('error.invalidFileType');
            } elseif ($authKey === '') {
                $errorMessage = $this->translate('error.missingAuthKey.v2');
            } elseif ($this->isSameLanguagePair($languageService, $languageSource, $languageDest)) {
                $errorMessage = $this->translate('error.sameLanguage');
            } else {
                $safeOriginalName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
                $fileName = 'translated_' . date('Ymd-His') . '_' . $safeOriginalName;
                $targetDir = 'fileadmin/user_upload/translated/';
                $absoluteTargetDir = GeneralUtility::getFileAbsFileName($targetDir);

                if (!is_dir($absoluteTargetDir)) {
                    GeneralUtility::mkdir_deep($absoluteTargetDir);
                }

                $sourcePath = $absoluteTargetDir . 'original_' . $fileName;
                $targetPath = $absoluteTargetDir . $fileName;

                if (!move_uploaded_file($tmpFile, $sourcePath)) {
                    $errorMessage = $this->translate('error.uploadSaveFailed');
                } else {
                    try {
                        $translationService->translateDocument(
                            $authKey,
                            $sourcePath,
                            $targetPath,
                            $languageSource,
                            $languageDest,
                            $selectedGlossaryId
                        );

                        $translatedFilePath = '/' . $targetDir . $fileName;
                        $translatedFileName = $fileName;

                        if (file_exists($sourcePath)) {
                            unlink($sourcePath);
                        }
                    } catch (\Throwable $exception) {
                        $errorMessage = $this->translate('error.documentTranslation', [$exception->getMessage()]);

                        if (file_exists($sourcePath)) {
                            unlink($sourcePath);
                        }
                    }
                }
            }
        }

        $this->view->assignMultiple([
            'frontendAccessHeader' => $frontendAccessService->renderAccessHeader($this->request),
            'translatedFilePath' => $translatedFilePath,
            'translatedFileName' => $translatedFileName,
            'errorMessage' => $errorMessage,
            'language_source' => $languageSource,
            'language_ziel' => $languageDest,
            'languages' => $languages,
            'sourceLanguages' => $sourceLanguages,
            'targetLanguages' => $targetLanguages,
            'useGlossary' => $selectedGlossaryId !== '',
            'glossaryAvailable' => $glossaryService->hasGlossaryForLanguagePair($languageSource, $languageDest),
            'glossaryCombinationsJson' => json_encode((object)$glossaryService->getGlossaryCombinations(), JSON_THROW_ON_ERROR),
            'glossaryOptions' => $glossaryService->getGlossaryOptionsForLanguagePair($languageSource, $languageDest),
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

    private function isSameLanguagePair(DeeplLanguageService $languageService, string $sourceLanguage, string $targetLanguage): bool
    {
        return $languageService->normalizeGlossaryLanguage($sourceLanguage) === $languageService->normalizeGlossaryLanguage($targetLanguage);
    }
}
