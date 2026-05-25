# PPL DeepL V2 Translate

PPL DeepL V2 Translate is a standalone TYPO3 12.4 extension for DeepL based text and file translation in frontend plugins and backend modules.

The extension belongs to the same product surface as PPL DeepL V3 Translate, but it is intentionally independent. V2 talks directly to DeepL through `deeplcom/deepl-php`; it does not require or load `ppl_deepl_v3_requests`.

## Features

- Frontend content element for interactive text translation.
- Frontend plugin for document/file translation.
- Backend modules for configuration, text translation and file translation.
- DeepL language fetch and local language approval.
- DeepL glossary fetch and local glossary approval.
- Optional writing style or tone selection where the DeepL PHP client supports it.
- Optional frontend access protection with PPL inline login or a TYPO3 login page.
- Local storage under `var/ppl_deepl_v2_translate/`.

## Architecture

V2 keeps the controller, template, validation and storage concepts aligned with V3, but the API boundary is different:

- Extension key: `ppl_deepl_v2_translate`
- Namespace: `Ppl\PplDeeplV2Translate`
- Composer package: `ppl/ppl-deepl-v2-translate`
- TYPO3 target: `12.4`
- API dependency: `deeplcom/deepl-php`
- API adapter: `Ppl\PplDeeplV2Translate\Service\Api\V2DeepLPhpAdapter`

All translation, language and glossary services use the local adapter interface. They should not instantiate unrelated API clients directly.

## Difference To V3

V2 and V3 are separate TYPO3 extensions with the same product layout and similar workflows.

V2:

- Uses `deeplcom/deepl-php`.
- Supports text translation, file translation, languages and glossaries.
- Supports DeepL writing style or tone when available through the V2 adapter.
- Does not depend on `ppl_deepl_v3_requests`.
- Does not expose V3-only style rules or custom instructions.

V3:

- Uses `ppl_deepl_v3_requests`.
- Keeps direct DeepL client usage out of the translate package.
- Adds V3-only capabilities such as style rules and custom instructions.
- Stores data under `var/ppl_deepl_v3_translate/`.

Both extensions can be developed with the same UI and business workflow in mind, but they must stay independent at Composer, namespace, storage and API-client level.

## Requirements

- TYPO3 CMS 12.4 LTS
- PHP 8.2 or newer
- `deeplcom/deepl-php` 1.18 or newer
- A DeepL API key

## Installation

Install the extension with Composer:

```bash
composer require ppl/ppl-deepl-v2-translate:^12.4
```

Run TYPO3 setup and clear caches:

```bash
vendor/bin/typo3 extension:setup
vendor/bin/typo3 cache:flush
```

Include the shipped TypoScript setup if it is not loaded automatically in your project.

## DeepL Configuration

Set the DeepL auth key in TYPO3 extension configuration:

- Extension key: `ppl_deepl_v2_translate`
- Setting: `authKey`

The same setting can be stored in `config/system/settings.php`:

```php
'EXTENSIONS' => [
    'ppl_deepl_v2_translate' => [
        'authKey' => 'your-deepl-auth-key',
    ],
],
```

Do not commit real API keys. The public package only ships empty/default configuration.

## Backend Workflow

The extension registers a PPL DeepL V2 backend module group with these modules:

- Configuration: fetch DeepL languages and glossaries, approve them locally and configure frontend access.
- V2 Translation: translate text in the backend with the approved language and glossary setup.
- V2 File Translation: translate supported files through DeepL.

The V2 module root is `ppl_deepl_v2`. The module paths remain compatible with the previous V2 URLs:

- `/module/ppl-deepl/v2-configuration`
- `/module/ppl-deepl/v2-translation`
- `/module/ppl-deepl/v2-file-translation`

## Frontend Workflow

Editors can add these content elements in TYPO3:

- PPL DeepL V2 Translation
- PPL DeepL V2 File Translation

Frontend access can be open, restricted to frontend users, restricted to backend users, or routed through the PPL inline login depending on configuration.

When using a TYPO3/felogin page, enable redirect handling in the felogin plugin so users return to the protected DeepL page after login.

## Storage

V2 writes local runtime metadata below:

```text
var/ppl_deepl_v2_translate/
```

Typical files include language approvals, glossary approvals and transient login rate-limit data. V2 must not write to V3 storage paths.

## Security Notes

- Store API keys outside the repository.
- Use HTTPS in production so frontend access cookies can be sent with the Secure flag.
- The PPL inline login uses signed `HttpOnly` cookies and validates local return URLs.
- Logout is handled through extension-specific parameters to avoid accidental GET logout side effects.

## Release Line

Version `12.4.x` targets TYPO3 12.4 LTS.

## License

This extension uses the same license line as PPL Rights Management: GNU General Public License version 2.0 or later. See [LICENSE](LICENSE).
