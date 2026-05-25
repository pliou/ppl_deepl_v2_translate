<?php

declare(strict_types=1);

namespace Ppl\PplDeeplV2Translate\Controller;

use Ppl\PplDeeplV2Translate\Service\DeeplConfigurationService;
use Ppl\PplDeeplV2Translate\Service\DeeplGlossaryService;
use Ppl\PplDeeplV2Translate\Service\FrontendAccessConfigurationService;
use Ppl\PplDeeplV2Translate\Service\LanguageConfigurationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

#[AsController]
final class BackendConfigurationController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly PageRenderer $pageRenderer,
        private readonly DeeplConfigurationService $configurationService,
        private readonly DeeplGlossaryService $glossaryService,
        private readonly LanguageConfigurationService $languageConfigurationService,
        private readonly FrontendAccessConfigurationService $frontendAccessConfigurationService
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->getBody($request);
        $authKey = $this->configurationService->getAuthKey();
        $messages = [];
        $activeConfigTab = $this->getActiveConfigTab($request, $body);
        $glossaries = $this->glossaryService->getSavedGlossaries();
        $languages = $this->languageConfigurationService->getSavedLanguages();
        $action = (string)($body['module_action'] ?? '');

        if ($action === 'save_frontend_access') {
            $activeConfigTab = 'frontend_access';
            try {
                $this->frontendAccessConfigurationService->saveConfiguration(
                    (string)($body['frontend_access_mode'] ?? ''),
                    (string)($body['login_page_uid'] ?? ''),
                    (string)($body['allow_frontend_users'] ?? '0'),
                    (string)($body['allow_backend_users'] ?? '0'),
                    (string)($body['show_logout'] ?? '0')
                );
                $messages[] = [
                    'type' => 'success',
                    'text' => $this->translate('message.frontendAccessSaved'),
                ];
            } catch (\Throwable $exception) {
                $messages[] = [
                    'type' => 'error',
                    'text' => $this->translate('message.frontendAccessSaveFailed', [$exception->getMessage()]),
                ];
            }
        } elseif (in_array($action, ['fetch_languages', 'save_remote_languages'], true)) {
            $activeConfigTab = 'languages';
            try {
                if ($authKey === '') {
                    throw new \RuntimeException($this->translate('error.missingAuthKey.v2'));
                }

                $languages = $this->languageConfigurationService->fetchRemoteLanguages($authKey);

                if ($action === 'save_remote_languages') {
                    $languages = $this->languageConfigurationService->saveLanguages(
                        $languages,
                        (array)($body['enabled_languages'] ?? [])
                    );
                    $messages[] = [
                        'type' => 'success',
                        'text' => $this->translate('message.languagesSaved', [count($languages)]),
                    ];
                } else {
                    $messages[] = [
                        'type' => 'success',
                        'text' => $this->translate('message.languagesFound', [count($languages)]),
                    ];
                }
            } catch (\Throwable $exception) {
                $messages[] = [
                    'type' => 'error',
                    'text' => $this->translate('message.languagesFetchFailed', [$exception->getMessage()]),
                ];
            }
        } elseif ($action === 'save_saved_languages') {
            $activeConfigTab = 'languages';
            $languages = $this->languageConfigurationService->saveLanguages(
                $languages,
                (array)($body['enabled_languages'] ?? [])
            );
            $messages[] = [
                'type' => 'success',
                'text' => $this->translate('message.languagesApproved', [count($this->languageConfigurationService->getEnabledLanguages())]),
            ];
        } elseif (in_array($action, ['fetch_glossaries', 'save_remote_glossaries'], true)) {
            $activeConfigTab = 'glossaries';
            try {
                if ($authKey === '') {
                    throw new \RuntimeException($this->translate('error.missingAuthKey.v2'));
                }

                $previouslySavedGlossaries = $this->glossaryService->getSavedGlossaries();
                $glossaries = $this->glossaryService->fetchRemoteGlossaries($authKey);
                $enabledIds = $previouslySavedGlossaries === []
                    ? array_map(static fn(array $glossary): string => (string)($glossary['id'] ?? ''), $glossaries)
                    : $this->glossaryService->getSelectedGlossaryIds();
                $glossaries = $this->markEnabled($glossaries, $enabledIds);

                if ($action === 'save_remote_glossaries') {
                    $glossaries = $this->glossaryService->saveGlossaries(
                        $glossaries,
                        (array)($body['enabled_glossaries'] ?? [])
                    );
                    $messages[] = [
                        'type' => 'success',
                        'text' => $this->translate('message.glossariesSaved', [count($glossaries), count($this->glossaryService->getSelectedGlossaryIds())]),
                    ];
                } else {
                    $messages[] = [
                        'type' => 'success',
                        'text' => $this->translate('message.glossariesFound', [count($glossaries)]),
                    ];
                }
            } catch (\Throwable $exception) {
                $messages[] = [
                    'type' => 'error',
                    'text' => $this->translate('message.glossariesFetchFailed', [$exception->getMessage()]),
                ];
            }
        } elseif ($action === 'save_saved_glossaries') {
            $activeConfigTab = 'glossaries';
            $glossaries = $this->glossaryService->saveGlossaries(
                $glossaries,
                (array)($body['enabled_glossaries'] ?? [])
            );
            $messages[] = [
                'type' => 'success',
                'text' => $this->translate('message.glossariesApproved', [count($this->glossaryService->getSelectedGlossaryIds())]),
            ];
        }

        $this->pageRenderer->addCssFile('EXT:ppl_deepl_v2_translate/Resources/Public/Css/backend.css');
        $this->pageRenderer->addJsFile('EXT:ppl_deepl_v2_translate/Resources/Public/Javascript/backend-scroll.js', 'module', true, false, '', true);
        $this->pageRenderer->addJsFile('EXT:ppl_deepl_v2_translate/Resources/Public/Javascript/backend-language-selection.js', 'module', true, false, '', true);

        $languageGroups = $this->languageConfigurationService->getApprovalLanguageGroups($languages);
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setModuleClass('ppl-deepl-v2-config-module');
        $moduleTemplate->setTitle($this->translate('config.title'));
        $moduleTemplate->assignMultiple([
            'activeConfigTab' => $activeConfigTab,
            'apiCapabilities' => $this->languageConfigurationService->getApiCapabilities(),
            'authKeyConfigured' => $authKey !== '',
            'disabledLanguages' => $languageGroups['disabled'],
            'enabledLanguages' => $languageGroups['enabled'],
            'frontendAccessConfiguration' => $this->frontendAccessConfigurationService->getConfiguration(),
            'glossaries' => $glossaries,
            'languages' => $languages,
            'messages' => $messages,
            'routeConfiguration' => (string)$this->uriBuilder->buildUriFromRoute('ppl_deepl_v2_configuration'),
            'routeConfigurationFrontendAccess' => (string)$this->uriBuilder->buildUriFromRoute('ppl_deepl_v2_configuration', ['config_tab' => 'frontend_access']),
            'routeConfigurationGlossaries' => (string)$this->uriBuilder->buildUriFromRoute('ppl_deepl_v2_configuration', ['config_tab' => 'glossaries']),
            'routeConfigurationLanguages' => (string)$this->uriBuilder->buildUriFromRoute('ppl_deepl_v2_configuration', ['config_tab' => 'languages']),
            'saveGlossaryAction' => $action === 'fetch_glossaries' ? 'save_remote_glossaries' : 'save_saved_glossaries',
            'saveLanguageAction' => $action === 'fetch_languages' ? 'save_remote_languages' : 'save_saved_languages',
            'saveAction' => $action === 'fetch_glossaries' ? 'save_remote_glossaries' : 'save_saved_glossaries',
        ]);

        return $moduleTemplate->renderResponse('Backend/Configuration');
    }

    private function getActiveConfigTab(ServerRequestInterface $request, array $body): string
    {
        $queryParams = $request->getQueryParams();
        $uriQueryParams = [];
        parse_str($request->getUri()->getQuery(), $uriQueryParams);

        $activeConfigTab = (string)($body['config_tab'] ?? $queryParams['config_tab'] ?? $uriQueryParams['config_tab'] ?? 'glossaries');

        return in_array($activeConfigTab, ['glossaries', 'languages', 'frontend_access'], true)
            ? $activeConfigTab
            : 'glossaries';
    }

    private function markEnabled(array $items, array $enabledIds): array
    {
        $enabledLookup = array_fill_keys(array_map('strval', $enabledIds), true);

        foreach ($items as $index => $item) {
            $items[$index]['enabled'] = isset($enabledLookup[(string)($item['id'] ?? '')]);
        }

        return $items;
    }

    private function getBody(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();

        return is_array($body) ? $body : [];
    }

    private function translate(string $key, array $arguments = []): string
    {
        return LocalizationUtility::translate($key, 'PplDeeplV2Translate', $arguments) ?? $key;
    }
}
