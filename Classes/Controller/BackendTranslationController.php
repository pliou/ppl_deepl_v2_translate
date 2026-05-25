<?php

declare(strict_types=1);

namespace Ppl\PplDeeplV2Translate\Controller;

use Ppl\PplDeeplV2Translate\Service\DeeplConfigurationService;
use Ppl\PplDeeplV2Translate\Service\DeeplGlossaryService;
use Ppl\PplDeeplV2Translate\Service\DeeplLanguageService;
use Ppl\PplDeeplV2Translate\Service\DeeplTranslationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

#[AsController]
final class BackendTranslationController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly PageRenderer $pageRenderer,
        private readonly DeeplConfigurationService $configurationService,
        private readonly DeeplLanguageService $languageService,
        private readonly DeeplGlossaryService $glossaryService,
        private readonly DeeplTranslationService $translationService
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->getBody($request);
        $activeTab = $this->getActiveTab($request, $body);
        $authKey = $this->configurationService->getAuthKey();
        $messages = [];

        $textData = $this->getDefaultTextData();
        $fileData = $this->getDefaultFileData();

        $action = (string)($body['module_action'] ?? '');

        if ($action === 'translate_text') {
            $activeTab = 'translation';
            $textData = $this->handleTextTranslation($body, $authKey);
        }

        if ($action === 'translate_file') {
            $activeTab = 'file';
            $fileData = $this->handleFileTranslation($request, $body, $authKey);
        }

        $this->pageRenderer->addCssFile('EXT:ppl_deepl_v2_translate/Resources/Public/Css/site.css');
        $this->pageRenderer->addCssFile('EXT:ppl_deepl_v2_translate/Resources/Public/Css/backend.css');
        $this->pageRenderer->addJsFile('EXT:ppl_deepl_v2_translate/Resources/Public/Javascript/backend-scroll.js', 'module', true, false, '', true);
        $this->pageRenderer->addJsFile('EXT:ppl_deepl_v2_translate/Resources/Public/Javascript/backend-copy.js', 'module', true, false, '', true);

        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setModuleClass('ppl-deepl-v2-translate-module');
        $moduleTemplate->setTitle($this->translate('backend.title.v2'));
        $backendControlData = [
            'labels' => [
                'copied' => $this->translate('message.copied'),
            ],
        ];
        $moduleTemplate->assignMultiple([
            'activeTab' => $activeTab,
            'apiCapabilities' => $this->translationService->getApiCapabilities(),
            'authKeyConfigured' => $authKey !== '',
            'backendControlDataJson' => json_encode($backendControlData, JSON_THROW_ON_ERROR),
            'fileData' => $fileData,
            'glossaryCombinationsJson' => json_encode((object)$this->glossaryService->getGlossaryCombinations(), JSON_THROW_ON_ERROR),
            'glossaryOptionsByCombinationJson' => json_encode((object)$this->glossaryService->getGlossaryOptionsByCombination(), JSON_THROW_ON_ERROR),
            'languages' => $this->languageService->getLanguages(),
            'sourceLanguages' => $this->languageService->getSourceLanguages(),
            'targetLanguages' => $this->languageService->getTargetLanguages(),
            'messages' => $messages,
            'routeFile' => $this->buildRouteUrl('ppl_deepl_v2_file_translation'),
            'routeTranslation' => $this->buildRouteUrl('ppl_deepl_v2_translation'),
            'textData' => $textData,
            'tones' => $this->translationService->getTones(),
            'writingStyles' => $this->translationService->getWritingStyles(),
        ]);

        return $moduleTemplate->renderResponse('Backend/Control');
    }

    private function handleTextTranslation(array $body, string $authKey): array
    {
        $data = $this->getDefaultTextData();
        $sourceLanguages = $this->languageService->getSourceLanguages();
        $targetLanguages = $this->languageService->getTargetLanguages();
        $writingStyles = $this->translationService->getWritingStyles();
        $tones = $this->translationService->getTones();

        $data['textarea'] = trim((string)($body['textarea'] ?? ''));
        $data['language_source'] = $this->normalizePostedSourceLanguage((string)($body['language_source'] ?? ''), $sourceLanguages, DeeplLanguageService::DEFAULT_SOURCE_LANGUAGE);
        $data['language_ziel'] = $this->normalizePostedLanguage((string)($body['language_ziel'] ?? ''), $targetLanguages, DeeplLanguageService::DEFAULT_TARGET_LANGUAGE);
        $data['glossary_id'] = trim((string)($body['glossary_id'] ?? ''));
        if (!$this->glossaryService->isGlossaryAvailableForLanguagePair($data['glossary_id'], $data['language_source'], $data['language_ziel'])) {
            $data['glossary_id'] = '';
        }
        $data['writing_style'] = array_key_exists((string)($body['writing_style'] ?? ''), $writingStyles)
            ? (string)$body['writing_style']
            : '';
        $data['tone'] = array_key_exists((string)($body['tone'] ?? ''), $tones)
            ? (string)$body['tone']
            : '';

        if ($data['writing_style'] !== '' && $data['tone'] !== '') {
            $data['tone'] = '';
        }

        if ($data['textarea'] === '') {
            $data['translationError'] = $this->translate('error.missingText');
        } elseif ($this->isSameLanguagePair($data['language_source'], $data['language_ziel'])) {
            $data['translationError'] = $this->translate('error.sameLanguage');
        } elseif ($authKey === '') {
            $data['translationError'] = $this->translate('error.missingAuthKey.v2');
        } else {
            try {
                $data['translatedText'] = $this->translationService->translateText(
                    $authKey,
                    $data['textarea'],
                    $data['language_source'],
                    $data['language_ziel'],
                    $data['glossary_id'],
                    $data['writing_style'],
                    $data['tone']
                );
            } catch (\Throwable $exception) {
                $data['translationError'] = $this->translate('error.translation', [$exception->getMessage()]);
            }
        }

        return $this->withGlossaryAvailability($data);
    }

    private function handleFileTranslation(ServerRequestInterface $request, array $body, string $authKey): array
    {
        $data = $this->getDefaultFileData();
        $sourceLanguages = $this->languageService->getSourceLanguages();
        $targetLanguages = $this->languageService->getTargetLanguages();
        $data['language_source'] = $this->normalizePostedSourceLanguage((string)($body['language_source'] ?? ''), $sourceLanguages, DeeplLanguageService::DEFAULT_SOURCE_LANGUAGE);
        $data['language_ziel'] = $this->normalizePostedLanguage((string)($body['language_ziel'] ?? ''), $targetLanguages, DeeplLanguageService::DEFAULT_TARGET_LANGUAGE);
        $data['glossary_id'] = trim((string)($body['glossary_id'] ?? ''));
        if (!$this->glossaryService->isGlossaryAvailableForLanguagePair($data['glossary_id'], $data['language_source'], $data['language_ziel'])) {
            $data['glossary_id'] = '';
        }

        $uploadedFile = $request->getUploadedFiles()['userfile'] ?? null;
        if (!$uploadedFile instanceof UploadedFileInterface || $uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $data['errorMessage'] = $this->translate('error.missingFile');
            return $this->withGlossaryAvailability($data);
        }

        $originalName = (string)$uploadedFile->getClientFilename();
        $originalExtension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['txt', 'pdf', 'docx', 'pptx'];

        if (!in_array($originalExtension, $allowedExtensions, true)) {
            $data['errorMessage'] = $this->translate('error.invalidFileType');
        } elseif ($authKey === '') {
            $data['errorMessage'] = $this->translate('error.missingAuthKey.v2');
        } elseif ($this->isSameLanguagePair($data['language_source'], $data['language_ziel'])) {
            $data['errorMessage'] = $this->translate('error.sameLanguage');
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

            try {
                $uploadedFile->moveTo($sourcePath);
                $this->translationService->translateDocument(
                    $authKey,
                    $sourcePath,
                    $targetPath,
                    $data['language_source'],
                    $data['language_ziel'],
                    $data['glossary_id']
                );
                $data['translatedFilePath'] = '/' . $targetDir . $fileName;
                $data['translatedFileName'] = $fileName;
            } catch (\Throwable $exception) {
                $data['errorMessage'] = $this->translate('error.documentTranslation', [$exception->getMessage()]);
            } finally {
                if (is_file($sourcePath)) {
                    unlink($sourcePath);
                }
            }
        }

        return $this->withGlossaryAvailability($data);
    }

    private function getDefaultTextData(): array
    {
        return $this->withGlossaryAvailability([
            'language_source' => DeeplLanguageService::DEFAULT_SOURCE_LANGUAGE,
            'language_ziel' => DeeplLanguageService::DEFAULT_TARGET_LANGUAGE,
            'glossary_id' => '',
            'textarea' => '',
            'tone' => '',
            'translatedText' => null,
            'translationError' => null,
            'writing_style' => '',
        ]);
    }

    private function getDefaultFileData(): array
    {
        return $this->withGlossaryAvailability([
            'errorMessage' => null,
            'glossary_id' => '',
            'language_source' => DeeplLanguageService::DEFAULT_SOURCE_LANGUAGE,
            'language_ziel' => DeeplLanguageService::DEFAULT_TARGET_LANGUAGE,
            'translatedFileName' => null,
            'translatedFilePath' => null,
        ]);
    }

    private function withGlossaryAvailability(array $data): array
    {
        $data['glossaryOptions'] = $this->glossaryService->getGlossaryOptionsForLanguagePair(
            (string)$data['language_source'],
            (string)$data['language_ziel']
        );
        $data['glossaryAvailable'] = $data['glossaryOptions'] !== [];

        if (!$this->glossaryService->isGlossaryAvailableForLanguagePair((string)($data['glossary_id'] ?? ''), (string)$data['language_source'], (string)$data['language_ziel'])) {
            $data['glossary_id'] = '';
        }
        $data['useGlossary'] = $data['glossary_id'] !== '';

        return $data;
    }

    private function normalizePostedLanguage(string $language, array $languages, string $fallback): string
    {
        return array_key_exists($language, $languages) ? $language : $fallback;
    }

    private function normalizePostedSourceLanguage(string $language, array $sourceLanguages, string $fallback): string
    {
        if (array_key_exists($language, $sourceLanguages)) {
            return $language;
        }

        $normalizedLanguage = $this->languageService->normalizeSourceLanguage($language);

        return array_key_exists($normalizedLanguage, $sourceLanguages) ? $normalizedLanguage : $fallback;
    }

    private function isSameLanguagePair(string $sourceLanguage, string $targetLanguage): bool
    {
        return $this->languageService->normalizeGlossaryLanguage($sourceLanguage) === $this->languageService->normalizeGlossaryLanguage($targetLanguage);
    }

    private function markSelectedGlossaries(array $glossaries, array $selectedGlossaryIds): array
    {
        $selectedLookup = array_fill_keys(array_map('strval', $selectedGlossaryIds), true);

        foreach ($glossaries as $index => $glossary) {
            $glossaries[$index]['selected'] = isset($selectedLookup[(string)($glossary['id'] ?? '')]);
        }

        return $glossaries;
    }

    private function getActiveTab(ServerRequestInterface $request, array $body): string
    {
        if (($body['active_tab'] ?? '') === 'file') {
            return 'file';
        }

        if (($body['active_tab'] ?? '') === 'translation') {
            return 'translation';
        }

        $path = (string)$request->getUri()->getPath();

        return str_contains($path, '/v2-file-translation') || str_contains($path, '/ppl-deepl-v2-file-translation')
            ? 'file'
            : 'translation';
    }

    private function getBody(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();

        return is_array($body) ? $body : [];
    }

    private function buildRouteUrl(string $routeName): string
    {
        return (string)$this->uriBuilder->buildUriFromRoute($routeName);
    }

    private function translate(string $key, array $arguments = []): string
    {
        return LocalizationUtility::translate($key, 'PplDeeplV2Translate', $arguments) ?? $key;
    }
}
