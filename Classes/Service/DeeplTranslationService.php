<?php

declare(strict_types=1);

namespace Ppl\PplDeeplV2Translate\Service;

use Ppl\PplDeeplV2Translate\Service\Api\DeepLApiAdapterInterface;

final class DeeplTranslationService
{
    public function __construct(
        private readonly DeeplLanguageService $languageService,
        private readonly DeeplGlossaryService $glossaryService,
        private readonly DeepLApiAdapterInterface $apiAdapter
    ) {}

    public function getWritingStyles(): array
    {
        return [
            'academic' => 'Academic',
            'business' => 'Business',
            'casual' => 'Casual',
            'simple' => 'Simple',
        ];
    }

    public function getTones(): array
    {
        return [
            'confident' => 'Confident',
            'diplomatic' => 'Diplomatic',
            'enthusiastic' => 'Enthusiastic',
            'friendly' => 'Friendly',
        ];
    }

    public function getApiCapabilities(): array
    {
        return $this->apiAdapter->getCapabilities();
    }

    public function translateText(
        string $authKey,
        string $inputText,
        string $sourceLanguage,
        string $targetLanguage,
        string $glossaryId,
        string $writingStyle,
        string $tone
    ): string {
        $glossaryId = $this->glossaryService->isGlossaryAvailableForLanguagePair($glossaryId, $sourceLanguage, $targetLanguage)
            ? $glossaryId
            : null;

        return $this->apiAdapter->translateText(
            $authKey,
            $inputText,
            $sourceLanguage,
            $targetLanguage,
            $glossaryId,
            $writingStyle,
            $tone
        );
    }

    public function translateDocument(
        string $authKey,
        string $sourcePath,
        string $targetPath,
        string $sourceLanguage,
        string $targetLanguage,
        string $glossaryId
    ): void {
        $glossaryId = $this->glossaryService->isGlossaryAvailableForLanguagePair($glossaryId, $sourceLanguage, $targetLanguage)
            ? $glossaryId
            : null;

        $this->apiAdapter->translateDocument(
            $authKey,
            $sourcePath,
            $targetPath,
            $sourceLanguage,
            $targetLanguage,
            $glossaryId
        );
    }
}
